<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    // تسجيل مستخدم جديد وإرسال OTP
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $otp = rand(100000, 999999);

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'otp_code'   => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        try {
            Mail::raw("رمز التحقق الخاص بك هو: $otp", function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('رمز التحقق');
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إرسال رمز التحقق',
                'error'   => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message'  => 'تم إرسال رمز التحقق إلى البريد الإلكتروني',
            'user_id'  => $user->id,
            'otp_code' => app()->environment('local') ? $otp : null
        ], 201);
    }

    // التحقق من OTP وتفعيل الحساب
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'otp_code' => 'required|numeric',
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['message' => 'البريد الإلكتروني غير مسجل'], 404);
        }
    
        if ($user->email_verified_at) {
            return response()->json(['message' => 'الحساب مؤكد بالفعل'], 400);
        }
    
        if ($user->otp_code != $request->otp_code) {
            return response()->json(['message' => 'رمز التحقق غير صحيح'], 422);
        }
    
        if ($user->otp_expires_at && Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'انتهت صلاحية رمز التحقق'], 422);
        }
    
        // تحديث حالة التفعيل
        $user->update([
            'email_verified_at' => Carbon::now(),
            'email_verified'    => true,
            'otp_code'          => null,
            'otp_expires_at'    => null,
        ]);
    
        // ✅ هنا بنعمل تسجيل دخول تلقائي بعد التأكيد
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $user->access_token_expires_at = Carbon::now()->addDays(90);
    
        $refreshToken = bin2hex(random_bytes(64));
        $user->refresh_token = Hash::make($refreshToken);
        $user->refresh_token_expires_at = Carbon::now()->addDays(90);
        $user->save();

        return response()->json([
            'message'                  => 'تم تأكيد الحساب وتسجيل الدخول بنجاح',
            'access_token'             => $accessToken,
            'access_token_expires_at'  => $user->access_token_expires_at,
            'refresh_token'            => $refreshToken,
            'refresh_token_expires_at' => $user->refresh_token_expires_at,
            'user'                     => $user->only(['id','name','email','email_verified_at','created_at','updated_at'])
        ], 200);
    }

    // تسجيل الدخول
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'يجب تأكيد الحساب أولاً'], 403);
        }

        // امسح أي Access Tokens قديمة
        $user->tokens()->delete();

        // إنشاء Access Token وصلاحية 90 يوم
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $user->access_token_expires_at = Carbon::now()->addDays(90);

        // إنشاء Refresh Token وصلاحية 90 يوم
        $refreshToken = bin2hex(random_bytes(64));
        $user->refresh_token = Hash::make($refreshToken);
        $user->refresh_token_expires_at = Carbon::now()->addDays(90);
        $user->save();

        return response()->json([
            'message'                  => 'تم تسجيل الدخول',
            'access_token'             => $accessToken,
            'access_token_expires_at'  => $user->access_token_expires_at,
            'refresh_token'            => $refreshToken,
            'refresh_token_expires_at' => $user->refresh_token_expires_at,
            'user'                     => $user->only(['id','name','email','email_verified_at','created_at','updated_at'])
        ], 200);
    }

    // تجديد Access Token باستخدام Refresh Token
    public function refreshToken(Request $request)
    {
        $request->validate(['refresh_token' => 'required|string']);

        $user = User::whereNotNull('refresh_token')->get()
            ->first(function ($u) use ($request) {
                return Hash::check($request->refresh_token, $u->refresh_token);
            });

        if (!$user) {
            return response()->json(['message' => 'Refresh Token غير صالح'], 401);
        }

        if (Carbon::now()->greaterThan($user->refresh_token_expires_at)) {
            $user->update([
                'refresh_token'            => null,
                'refresh_token_expires_at' => null
            ]);
            return response()->json(['message' => 'Refresh Token منتهي الصلاحية'], 401);
        }

        // امسح أي Access Tokens قديمة
        $user->tokens()->delete();

        // أنشئ Access Token جديد
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $user->access_token_expires_at = Carbon::now()->addDays(90);
        $user->save();

        return response()->json([
            'message'                 => 'تم تجديد التوكن بنجاح',
            'access_token'            => $accessToken,
            'access_token_expires_at' => $user->access_token_expires_at
        ], 200);
    }

    // بيانات المستخدم الحالي
    public function user(Request $request)
    {
        return response()->json(
            $request->user()->only(['id','name','email','email_verified_at','created_at','updated_at']),
            200
        );
    }

    // تسجيل الخروج
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            $request->user()->update([
                'refresh_token'            => null,
                'refresh_token_expires_at' => null,
                'access_token_expires_at'  => null
            ]);

            return response()->json(['message' => 'تم تسجيل الخروج بنجاح'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // داخل AuthController أو الأفضل MediaController
    public function uploadVideo(Request $request)
    {
        // ✅ تحقق من صحة الملف
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,wmv,flv|max:51200', // 50MB
        ]);

        try {
            // احفظ الملف داخل storage/app/public/videos
            $path = $request->file('video')->store('videos', 'public');

            // رجّع رابط الوصول للفيديو
            $url = asset('storage/' . $path);

            return response()->json([
                'message' => 'تم رفع الفيديو بنجاح',
                'video_url' => $url,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء رفع الفيديو',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}