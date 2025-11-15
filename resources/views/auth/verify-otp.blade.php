<!DOCTYPE html>
<html>
<head>
    <title>تأكيد الحساب</title>
</head>
<body>
    <form method="POST" action="{{ route('verify.otp') }}">
        @csrf
        <input type="email" name="email" placeholder="Email" value="{{ session('email') }}" readonly required>
        <input type="text" name="otp_code" placeholder="OTP" required>
        <button type="submit">تحقق</button>

    </form>
</body>
</html>
