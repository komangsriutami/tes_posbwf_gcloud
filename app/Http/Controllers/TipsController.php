<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tips;
use App;
use Datatables;
use DB;
use Validator;
use Illuminate\Support\Str;

class TipsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('tips.index');
    }

    public function list_tips(Request $request)
    {
        $order = $request->get('order');
        $columns = $request->get('columns');
        $order_column = $columns[$order[0]['column']]['data'];
        $order_dir = $order[0]['dir'];

        DB::statement(DB::raw('set @rownum = 0'));
        $data = Tips::select([DB::raw('@rownum  := @rownum  + 1 AS no'),'tb_tips.*'])
        ->where(function($query) use($request){
            $query->where('tb_tips.is_deleted','=','0');
        });
        
        $datatables = Datatables::of($data);
        return $datatables
        ->filter(function($query) use($request,$order_column,$order_dir){
            $query->where(function($query) use($request){
                $query->orwhere('title','LIKE','%'.$request->get('search')['value'].'%');
            });
        })  
        ->addcolumn('action', function($data) {
            $btn = '<div class="btn-group">';
            $btn .= '<span class="btn btn-primary" onClick="edit_data('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i></span>';
            $btn .= '<span class="btn btn-danger" onClick="delete_data('.$data->id.')" data-toggle="tooltip" data-placement="top" title="Hapus Data"><i class="fa fa-times"></i></span>';
            $btn .='</div>';
            return $btn;
        })    
        ->rawColumns(['action'])
        ->addIndexColumn()
        ->make(true);  
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    	$tips = new Tips;

        return view('tips.create')->with(compact('tips'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $tips = new Tips;
        $tips->fill($request->except('_token'));

        $validator =  Validator::make($request->all(), [
                'title' => 'required|unique:tb_tips',
                'image' => 'nullable|image|mimes:jpeg,jpg,png||max:1024',
                'content' => 'required'
            ]);

        if($validator->fails()){
            return view('tips.create')->with(compact('tips'))->withErrors($validator);
        }else{
            if ($request->has('image')) {
                $image = $request->image;
                $imageName = Str::slug(strtolower($request->title), '_').'-'.date('dmYHis').'.'.$image->guessExtension();
                $upload = $image->move(public_path('uploads/tips'), $imageName);

                $tips->image = $imageName;
            }
            $tips->save_plus();
            session()->flash('success', 'Sukses menyimpan data!');
            return redirect('tips');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tips = Tips::find($id);
        return view('tips.edit')->with(compact('tips'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //dd($request->all());
        $tips = Tips::find($id);
        $tips->fill($request->except('_token'));

        $validator =  Validator::make($request->all(), [
                'title' => 'required|unique:tb_tips,title'.($id ? ",$id" : '').',id',
                'image' => 'nullable|image|mimes:jpeg,jpg,png||max:1024',
                'content' => 'required'
            ]);
            
        if($validator->fails()){
            echo json_encode(array('status' => 0, 'errors' => $validator->errors()));
        }else{
            if ($request->has('image')) {
                $path = 'uploads/tips/';
                if (file_exists(public_path($path.$data->image)) && !is_null($data->image) && $data->image != '') {
                    $del_image = unlink(public_path($path.$data->image));
                }

                $image = $request->image;
                $imageName = Str::slug(strtolower($request->title), '_').'-'.date('dmYHis').'.'.$image->guessExtension();
                $upload = $image->move(public_path('uploads/tips'), $imageName);

                $tips->image = $imageName;
            }
            $tips->save_edit();
            echo json_encode(array('status' => 1));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tips = Tips::find($id);
        $tips->is_deleted = 1;
        if($tips->save()){
            echo 1;
        }else{
            echo 0;
        }
    }
}
