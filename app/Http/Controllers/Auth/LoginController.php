<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function registerUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $otp = rand(100000, 999999); // رقم مؤقت 6 أرقام

        DB::table('users')->insert([
            'name' => $request->email, // لو مفيش حقل name
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp_code' => $otp,
            'email_verified' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::raw("رمز التحقق الخاص بك هو: $otp", function($message) use ($request) {
            $message->to($request->email)
                    ->subject('رمز التحقق');
        });

        return redirect()->route('show.otp')->with([
            'success' => 'تم التسجيل بنجاح، تحقق من الإيميل لإكمال التسجيل',
            'email' => $request->email
        ]);
    }

    // لازم تكون جوه الكلاس
    public function showOtpForm()
    {
        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $user = DB::table('users')
                  ->where('email', $request->email)
                  ->where('otp_code', $request->otp_code)
                  ->first();

        if($user) {
            DB::table('users')->where('id', $user->id)->update([
                'email_verified' => true,
                'otp_code' => null
            ]);

            return redirect('/login')->with('success', 'تم تأكيد الحساب بنجاح');
        }

        return back()->withErrors(['otp_code' => 'رمز التحقق غير صحيح']);
    }
}