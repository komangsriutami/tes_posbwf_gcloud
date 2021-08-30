<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\RegistrasiPasien;
use App\MasterDokter;
use App\RegistrasiPasienCart;
use App\Pasien;
use App\Tips;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Auth;
class HomepageController extends Controller
{
    public function index()
    {
        return view('homepage.index');
    }
    public function about()
    {
        return view('homepage.about');
    }
    public function outlet()
    {
        return view('homepage.outlet');
    }
    public function contact()
    {
        return view('homepage.contact');
    }
    public function tips()
    {
        $data = Tips::where('is_deleted', 0)->orderBy('created_at', 'DESC')->get();
        return view('homepage.tips', compact('data'));
    }
    public function tipsDetails($slug)
    {
        $data = Tips::where('slug', $slug)->firstOrFail();
        return view('homepage.tips-details', compact('data'));
    }

    public function medicalRecord(Request $request)
    {
        $session_id = session()->getId();
        $cart = RegistrasiPasienCart::firstOrCreate(['session_id'=>$session_id]);
        return view('homepage.medical-record', compact('cart'));
    }
    public function medicalRecordSubmit(Request $request)
    {
        // if($request->has('sign_up')){
            $rule = [
                'is_pernah_berobat' => 'required'
            ];
    
            $validator = Validator::make($request->all(), $rule);
    
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            
            $session_id = session()->getId();
            $cart = RegistrasiPasienCart::firstOrCreate(['session_id'=>$session_id]);
            if($cart){
                $cart->is_pernah_berobat = $request->is_pernah_berobat;
                $cart->save();
                
                return redirect()->route('doctor');
            }else{
                return redirect()->back()->with('message', 'Terjadi kesalahan.');
            }

        // }else if($request->has('sign_in')){
        //     dd('sign_in');
            
    
        //     // $pasien = Pasien::where('nomor_rekam_medis', $request->nomor_rekam_medis)->whereNotNull('nomor_rekam_medis')->first();
        //     // if($pasien){
        //     //     $cart->id_pasien = $pasien->id;
        //     // }

        // }else{
        //     return redirect()->back()->with('message', 'Terjadi kesalahan.');

        // }
    }

    public function doctor()
    {
        $data = MasterDokter::get();
        $session_id = session()->getId();
        $cart = RegistrasiPasienCart::where('session_id', $session_id)->first();
        if($cart){
            if($cart->is_pernah_berobat){
                return view('homepage.doctor', compact('data', 'cart'));
            }
        }
        return redirect()->route('medical_record');
    }
    public function doctorSelect(Request $request)
    {
        $session_id = session()->getId();
        $cart = RegistrasiPasienCart::firstOrCreate(['session_id'=>$session_id]);
        $cart->doctor_id = $request->doctor_id;
        $cart->save();
        return redirect()->route('schedule');
    }
    public function schedule()
    {
        $session_id = session()->getId();
        $cart = RegistrasiPasienCart::where('session_id', $session_id)->first();
        if($cart){
            if($cart->doctor_id){
                return view('homepage.schedule', compact('cart'));
            }
        }
        return redirect()->route('doctor');
    }
    public function scheduleSelect(Request $request)
    {
        $session_id = session()->getId();
        $cart = RegistrasiPasienCart::firstOrCreate(['session_id'=>$session_id]);
        $cart->booking_date = Carbon::parse($request->book)->format('Y-m-d h:i:s');
        $cart->save();
        return redirect()->route('register');
    }
    public function register()
    {
        $session_id = session()->getId();
        $cart = RegistrasiPasienCart::where('session_id', $session_id)->first();
        if($cart){
            if($cart->booking_date){
                return view('homepage.register', compact('cart'));
            }
        }
        return redirect()->route('schedule');
    }
    public function registerSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'tgl_lahir' => 'required|date',
            'id_jenis_kelamin' => 'required|exists:tb_m_jenis_kelamin,id',
            // 'id_status_perkawinan' => 'required|exists:tb_status_perkawinan,id',
            // 'job' => 'required',
            'telepon' => 'required|numeric',
            'alamat' => 'required',
            // 'is_pernah_berobat' => 'required',
            'alergi_obat' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $session_id = session()->getId();
        $cart = RegistrasiPasienCart::where('session_id', $session_id)->first();
        if($cart){
            $booking_date = Carbon::parse($cart->booking_date)->format('Y-m-d');

            $data = new RegistrasiPasien;
            $data->nama = $request->nama;
            $data->tgl_lahir = $request->tgl_lahir;
            $data->id_jenis_kelamin = $request->id_jenis_kelamin;
            // $data->id_status_perkawinan = $request->id_status_perkawinan;
            $data->telepon = $request->telepon;
            $data->alamat = $request->alamat;
            $data->is_pernah_berobat = $cart->is_pernah_berobat;
            $data->alergi_obat = $request->alergi_obat;
            $data->doctor_id = $cart->doctor_id;
            $data->tgl_periksa = $booking_date;
            $data->no_urut = RegistrasiPasienCart::generateNoUrut($booking_date);
            $data->id_pasien = $request->id_pasien;
            $data->save();

            if(!$data->id_pasien){
                $pasien = Pasien::firstOrCreate(['email'=>$request->email]);
                $pasien->email = $request->email;
                $pasien->nama = $data->nama;
                $pasien->tgl_lahir = $data->tgl_lahir;
                $pasien->id_jenis_kelamin = $data->id_jenis_kelamin;
                $pasien->alamat = $data->alamat;
                $pasien->save();
                $pasien->nomor_rekam_medis = $pasien->generateMedicalNumber();
                $pasien->save();
                
                $string = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $password = substr(str_shuffle(str_repeat($string, 8)), 0, 8);
                
                $pasien->password = Hash::make($password);
                $pasien->save();

                \Mail::send(
                    'emails.pasien-akun',
                    compact('pasien', 'password'),
                    function ($m) use ($pasien) {
                        $m->to($pasien->email, $pasien->nama);
                        $m->subject('[APOTEK BWF] Akun');
                    }
                );
                
                $data->id_pasien = $pasien->id;
                $data->save();
            }
            
            $cart->delete();
            
            return view('homepage.register-success');
        }else{
            return redirect()->back()->with('message', 'Terjadi kesalahan.');
        }
    }

    public function loginSubmit(Request $request)
    {
        $session_id = session()->getId();
        $cart = RegistrasiPasienCart::firstOrCreate(['session_id'=>$session_id]);
        $cart->is_pernah_berobat = 1;
        $cart->save();

        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:tb_m_pasien|min:5|max:191',
            'password' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if(Auth::guard('patient')->attempt($request->only('email','password'),$request->filled('remember'))){
            
            $session_id = session()->getId();
            $cart = RegistrasiPasienCart::firstOrCreate(['session_id'=>$session_id]);
            $cart->is_pernah_berobat = 1;
            $cart->save();

            $patient = Auth::guard('patient')->user();
            $cart->nama = $patient->nama;
            $cart->tgl_lahir = $patient->tgl_lahir;
            $cart->id_jenis_kelamin = $patient->id_jenis_kelamin;
            // $cart->id_status_perkawinan = $patient->xxxxxxxxxxxxxxx;
            $cart->telepon = $patient->telepon;
            $cart->email = $patient->email;
            $cart->alamat = $patient->alamat;
            // $cart->alergi_obat = $patient->xxxxxxxxxxxxxxx;
            $cart->id_pasien = $patient->id;
            $cart->save();
            
            return redirect()
                ->intended(route('doctor'))
                ->with('active','You are Logged in as Admin!');

        }else{
            // Auth::guard('patient')->logout();
            return redirect()
                ->back()
                ->withInput($request->only('email'))
                ->withErrors(
                    [
                        'active' => 'Ada kesalahan email dan password atau akun belum terdaftar.'
                    ]
                );

        }
    }
    public function sessionDisplay(Request $request)
    {
        dd(session()->all());
    }
}
