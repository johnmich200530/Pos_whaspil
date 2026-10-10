<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('pos_employee')) {
            return redirect()->route('dashboard');
        }
        return view('pos.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $employee = Employee::where('Username', $request->username)->first();

        if (!$employee || !Hash::check($request->password, $employee->Password)) {
            return back()->withErrors(['username' => 'Invalid username or password.']);
        }

        session(['pos_employee' => $employee]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('pos_employee');
        return redirect()->route('login');
    }
}
