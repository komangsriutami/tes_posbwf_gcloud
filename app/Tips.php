<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Auth;
use Illuminate\Support\Str;

class Tips extends Model
{
    protected $table = 'tb_tips';
    public $primaryKey = 'id';
    protected $fillable = ['title','image','content'];

    public function validate(){
    	return Validator::make((array)$this->attributes, [
            'title' => 'required|unique:tb_tips',
            'content' => 'required'
        ]);
    }

    public function save_plus(){
        $this->slug = Str::slug($this->title, '-');
        $this->created_by = Auth::user()->id;
        $this->save();
    }

    public function save_edit(){
        $this->slug = Str::slug($this->title, '-');
        $this->updated_by = Auth::user()->id;
        $this->save();
    }

    public function displayImage()
    {
        if($this->image){
            return asset('/protected/public/uploads/tips/'.$this->image);
        }else{
            return '/protected/public/img/default.jpg';
        }
    }
}
