<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payout Received</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f5f5f5; padding:30px;">
    <div style="max-width:600px;background:white;padding:30px;border-radius:10px;margin:auto;">
        <h2>Payout Sent</h2>
        <p>Hello {{ $shopName }},</p>
        <p>Good news — a payout has been released to your account for order #{{ $orderId }}.</p>
        <h1 style="font-size:36px;color:#0e0e11;">₱{{ $amount }}</h1>
        <p>This amount reflects your earnings after the platform commission for this order.</p>
        <br>
        <p>Regards,<br><strong>Zypher Team</strong></p>
    </div>
</body>
</html>