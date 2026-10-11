<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; background:#f5f5f5; padding:30px;">

    <div style="max-width:600px;background:white;padding:30px;border-radius:10px;margin:auto;">

        <h2>Password Reset</h2>

        <p>Hello,</p>

        <p>We received a request to reset your password.</p>

        <p>Your One-Time Password (OTP) is:</p>

        <h1 style="font-size:40px;letter-spacing:10px;color:#4f46e5;">
            {{ $otp }}
        </h1>

        <p>
            This OTP will expire in
            <strong>10 minutes.</strong>
        </p>

        <p>
            If you didn't request this, simply ignore this email.
        </p>

        <br>

        <p>
            Regards,<br>
            <strong>Zypher Team</strong>
        </p>

    </div>

</body>

</html>