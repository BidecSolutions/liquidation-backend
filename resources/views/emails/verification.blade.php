<!DOCTYPE html>
<html>
<head>
    <title>Login Verification</title>
</head>
<body>
    <p>Hello {{ $user->name ?? 'User' }},</p>
    <p>Your login verification code is:</p>
    <h2>{{ $code }}</h2>
    <p>This code will expire in 5 minutes.</p>
</body>
</html>
