<!DOCTYPE html>
<html>
<head>
    <title>Your OTP Code</title>
</head>
<body>
    <h2>Hello {{ $user->name }},</h2>
    <p>Your one-time password is: <strong>{{ $otp }}</strong></p>
    <p>This code expires in 10 minutes.</p>
</body>
</html>