<?php
namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended('dashboard');
        }
        
        return view('backend.pages.auth.index');
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'status' => 1
        ];
        $remember = $request->has('remember') ? true : false;
        if (auth()->attempt($credentials, $remember)) {
            $user = Auth::user();
            $user->last_login_at = Carbon::now();
            $user->last_login_ip = $request->ip();
            $user->login_attempts = 0; 
            $user->save();
            $request->session()->regenerate();
            return redirect()->intended('dashboard')
                   ->with('success', 'Welcome back, ' . $user->name . '!');
        } else {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->increment('login_attempts');                
                if ($user->status == 0) {
                    return redirect()->back()
                           ->withInput($request->only('email'))
                           ->with('error', 'Your account is inactive. Please contact administrator.');
                }
            }

            return redirect()->back()
                   ->withInput($request->only('email'))
                   ->with('error', 'Invalid email or password');
        }
    }
    public function logout(Request $request) 
    {
        $user = Auth::user();        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Session::flush();
        return redirect(url('admin/login'))->with('success', 'You have been logged out successfully');
    }

    public function showForgotForm()
    {
        return view('backend.pages.auth.forgot');
    }
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);
        return redirect()->back()
               ->with('success', 'Password reset link sent to your email');
    }
}