<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function prosesloginadmin(Request $request)
    {
        if(Auth::guard('user')->attempt(['email' => $request->email, 'password' => $request->password])) {
            return redirect('/panel/dashboardadmin');
        } else {
            return redirect('/panel')->with(['warning' => 'Email atau Password Salah!']);
        }
    }
    
    public function proseslogin(Request $request)
    {
        //$credentials = $request->only('nisn', 'password');

        //Debugging
        //dd($credentials);
        if(Auth::guard('murid')->attempt(['nisn' => $request->nisn, 'password' => $request->password])) {
            return redirect('/dashboard');
        } else {
            //$pass = 123;
            //echo Hash::make($pass);
            return redirect('/')->with(['warning' => 'NISN / Password Salah!']);
        }
    }

    public function proseslogout()
    {
        if(Auth::guard('murid')->check()){
            Auth::guard('murid')->logout();
            return redirect('/');
        }
    }

    public function proseslogoutadmin()
    {
        if(Auth::guard('user')->check()){
            Auth::guard('user')->logout();
            return redirect('/panel');
        }
    }
}
