<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use App\User;
use App\RbacMenu;
use App\RbacPermission;
use App\RbacRole;
use App\RbacRolePermission;
use App\RbacUserRole;
use App\RbacUserApotek;
use App\MasterApotek;
use App\MasterGroupApotek;
use App\MasterTahun;

use Auth;
use Route;
use Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login_admin() {
        return view('auth.login_pt');
    }

    public function login_outlet() {
        return view('auth.login_outlet');
    }

    public function login_outlet_check(Request $request)
    {
        $apotek = MasterApotek::where('kode_apotek', $request->kode_apotek)->first();
        if(!empty($apotek)) {
            $user = User::where('username','=',$request->username)->first();
            $cekuser = User::where('username','=',$request->username)->count();
            $cek_apotek_akses = RbacUserApotek::where('id_user', $user->id)->where('id_apotek', $apotek->id)->first();

            if($cekuser>=1){
                if(!empty($cek_apotek_akses)) {
                    if (Auth::guard()->attempt(['username' => $request->username, 'password' => $request->password], $request->remember)) {
                        $role_list = array();
                        $actions = array();
                        $user_roles = RbacUserRole::leftJoin('rbac_roles','rbac_roles.id', '=', 'rbac_user_role.id_role')
                                    ->where("id_user", Auth::id())
                                    ->orderBy('rbac_roles.is_superadmin', 'DESC')
                                    ->get();

                        session(['super_admin' => 0]);
                        foreach ($user_roles as $user_role) {
                            if($user_role->is_superadmin==1){
                                session(['super_admin' => 1]);
                            }

                            array_push($role_list, $user_role->nama);
                        }

                        if(!empty($user_roles)){
                            session(['nama_role_active'=>$user_roles[0]->nama]);
                            session(['id_role_active'=>$user_roles[0]->id]);
                            $menus = array();

                            $role_permissions = RbacRolePermission::where("id_role", $user_roles[0]->id)->get();
                            foreach ($role_permissions as $role_permission) {
                                $permission = RbacPermission::find($role_permission->id_permission);
                                $actions[] = $permission->nama;

                                $menus[] = $permission->id_menu;
                            }

                            $menu = RbacMenu::where('is_deleted', 0)->whereIn('id', $menus)->orderBy('weight')->get();

                            $parents = array();
                            foreach ($menu as $key => $val) {
                                if($val->parent == 0) {
                                    $data_parent = RbacMenu::find($val->id);
                                    $parents[] = $data_parent->id;
                                } else {
                                    $data_parent = RbacMenu::find($val->parent);
                                    $parents[] = $data_parent->id;
                                }

                            }

                            $parent_menu = RbacMenu::where('is_deleted', 0)->whereIn('id', $parents)->orderBy('weight')->get();

                            foreach ($parent_menu as $key => $obj) {
                                $sub_menu = array();
                                if ($obj->link == "#") {
                                    foreach ($menu as $key => $val) {
                                        $sub_sub_menu = array();
                                        if($val->parent == $obj->id) {
                                            if($val->link == "#") {
                                                $val->link == "#";
                                                $sub_sub_menu = RbacMenu::where('is_deleted', 0)->where('sub_parent', $val->id)->orderBy('weight')->get();
                                                $val->subsubmenu = $sub_sub_menu;
                                                $val->ada_sub_sub = 1;
                                                $sub_menu[] = $val;
                                            } else {
                                                $obj->subsubmenu = "";
                                                $obj->ada_sub_sub = 0;
                                                $sub_menu[] = $val;
                                            }
                                        }
                                    }
                                    $obj->link == "#";
                                    $obj->submenu = $sub_menu;
                                    $obj->ada_sub = 1;

                                } else {
                                    $obj->submenu = "";
                                    $obj->ada_sub = 0;
                                }
                            }
                        }
                        $apotek_group = MasterGroupApotek::find($apotek->id_group_apotek);

                        session(['nama_apotek_singkat_active'=>strtolower($apotek->nama_singkat)]);
                        session(['nama_apotek_panjang_active'=>$apotek->nama_panjang]);
                        session(['nama_apotek_active'=>$apotek->nama_singkat]);
                        session(['kode_apotek_active'=>$apotek->kode_apotek]);
                        session(['id_apotek_active'=>$apotek->id]);

                        $apoteks = MasterApotek::where('is_deleted', 0)->where('id_group_apotek', Auth::user()->id_group_apotek)->get();
                        $tahuns = MasterTahun::orderby('id', 'DESC')->get();
                        session(['id_tahun_active'=>date('Y')]);

                        $_SESSION["isLogedIn"] = 1;
                        session(['actions' => $actions]);
                        session(['menu' => $parent_menu]);
                        session(['apoteks' => $apoteks]);
                        session(['tahuns' => $tahuns]);
                        session(['apotek_group' => $apotek_group]);
                        session(['isLogedIn' => 1]);
                        session(['role_list' => $role_list]);
                        session(['user_roles' => $user_roles]);
                        session(['is_status_login' => '2']);
                        session(['status_login' => 'Outlet']);

                        return redirect()->intended(route('home'));
                    } else{
                        return redirect()->back()->withInput($request->only('username', 'remember', 'kode_apotek'))->withErrors([
                            'password' => 'Password yang anda masukan tidak tesuai, silakan periksa dan login kembali.',
                        ]);
                    }
                } else {
                    return redirect()->back()->withInput($request->only('username', 'remember', 'kode_apotek'))->withErrors([
                            'kode_apotek' => 'Anda tidak terdaftar sebagai staf diapotek ini, silakan hubungi administrator atau kepala outlet anda.',
                        ]);
                }
            } else {
                return redirect()->intended('login_outlet')->withErrors([
                    'username' => 'Username <strong>'.$request->username.'</strong> tidak terdaftar, silakan periksa dan login kembali.',
                ]);
            }
        } else {
            return redirect()->back()->withInput($request->only('username', 'remember', 'kode_apotek'))->withErrors([
                'kode_apotek' => 'Kode apotek yang anda masukan tidak tesuai, silakan periksa dan login kembali.',
            ]);
        }
    }

    public function login_admin_check(Request $request)
    {
        $user = User::where('username','=',$request->username)->first();
        $cekuser = User::where('username','=',$request->username)->count();

        if($cekuser >=1 ){
            if($user->is_admin == 1) {
                if (Auth::guard()->attempt(['username' => $request->username, 'password' => $request->password], $request->remember)) {
                    $role_list = array();
                    $actions = array();
                    $user_roles = RbacUserRole::leftJoin('rbac_roles','rbac_roles.id', '=', 'rbac_user_role.id_role')
                                ->where("id_user", Auth::id())
                                ->orderBy('rbac_roles.is_superadmin', 'DESC')
                                ->get();

                    session(['super_admin' => 0]);
                    foreach ($user_roles as $user_role) {
                        if($user_role->is_superadmin==1){
                            session(['super_admin' => 1]);
                        }

                        array_push($role_list, $user_role->nama);
                    }

                    if(!empty($user_roles)){
                        session(['nama_role_active'=>$user_roles[0]->nama]);
                        session(['id_role_active'=>$user_roles[0]->id]);
                        $menus = array();

                        $role_permissions = RbacRolePermission::where("id_role", $user_roles[0]->id)->get();
                        foreach ($role_permissions as $role_permission) {
                            $permission = RbacPermission::find($role_permission->id_permission);
                            $actions[] = $permission->nama;

                            $menus[] = $permission->id_menu;
                        }

                        $menu = RbacMenu::where('is_deleted', 0)->whereIn('id', $menus)->orderBy('weight')->get();

                        $parents = array();
                        foreach ($menu as $key => $val) {
                            if($val->parent == 0) {
                                $data_parent = RbacMenu::find($val->id);
                                $parents[] = $data_parent->id;
                            } else {
                                $data_parent = RbacMenu::find($val->parent);
                                $parents[] = $data_parent->id;
                            }

                        }

                        $parent_menu = RbacMenu::where('is_deleted', 0)->whereIn('id', $parents)->orderBy('weight')->get();

                        foreach ($parent_menu as $key => $obj) {
                            $sub_menu = array();
                            if ($obj->link == "#") {
                                foreach ($menu as $key => $val) {
                                    $sub_sub_menu = array();
                                    if($val->parent == $obj->id) {
                                        if($val->link == "#") {
                                            $val->link == "#";
                                            $sub_sub_menu = RbacMenu::where('is_deleted', 0)->where('sub_parent', $val->id)->orderBy('weight')->get();
                                            $val->subsubmenu = $sub_sub_menu;
                                            $val->ada_sub_sub = 1;
                                            $sub_menu[] = $val;
                                        } else {
                                            $obj->subsubmenu = "";
                                            $obj->ada_sub_sub = 0;
                                            $sub_menu[] = $val;
                                        }
                                    }
                                }
                                $obj->link == "#";
                                $obj->submenu = $sub_menu;
                                $obj->ada_sub = 1;

                            } else {
                                $obj->submenu = "";
                                $obj->ada_sub = 0;
                            }
                        }
                    }

                    $apotek_group = MasterGroupApotek::find(Auth::user()->id_group_apotek);

                    $apoteks = MasterApotek::where('is_deleted', 0)->where('id_group_apotek', Auth::user()->id_group_apotek)->get();
                    $tahuns = MasterTahun::orderby('id', 'DESC')->get();
                    session(['id_tahun_active'=>date('Y')]);

                    $_SESSION["isLogedIn"] = 1;
                    session(['actions' => $actions]);
                    session(['menu' => $parent_menu]);
                    session(['apoteks' => $apoteks]);
                    session(['tahuns' => $tahuns]);
                    session(['apotek_group' => $apotek_group]);
                    session(['isLogedIn' => 1]);
                    session(['role_list' => $role_list]);
                    session(['user_roles' => $user_roles]);
                    session(['is_status_login' => '1']);
                    session(['status_login' => 'PT']);

                    return redirect()->intended(route('home'));
                } else{
                    return redirect()->back()->withInput($request->only('username', 'remember'))->withErrors([
                        'password' => 'Password yang anda masukan tidak tesuai, silakan periksa dan login kembali.',
                    ]);
                }
            } else {
                return redirect()->intended('login_admin')->withErrors([
                    'username' => 'Username '.$request->username.', tidak terdaftar pada sebagai staff PT, silakan periksa dan login kembali.',
                ]);
            }
        } else {
            return redirect()->intended('login_admin')->withErrors([
                'username' => 'Username '.$request->username.', tidak terdaftar, silakan periksa dan login kembali.',
            ]);
        }
    }

    public function logout(Request $request) {
        Session::flush();
        Session::forget('super_admin');
        Session::forget('nama_role_active');
        Session::forget('id_role_active');
        Session::forget('actions');
        Session::forget('apoteks');
        Session::forget('isLogedIn');
        Session::forget('role_list');
        Session::forget('user_roles');
        Session::forget('nama_apotek_panjang_active');
        Session::forget('nama_apotek_active');
        Session::forget('id_apotek_active');
        Auth::logout();
        return redirect()->intended('/');
    }
}
