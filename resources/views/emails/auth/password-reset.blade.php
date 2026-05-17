<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; color: #111827; margin: 0; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #f3f4f6; }
        .header { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 20px; }
        .text { font-size: 16px; line-height: 1.6; color: #4b5563; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">Reset Your Password</div>
        <p class="text">Hi {{ $name }},</p>
        <p class="text">You are receiving this email because we received a password reset request for your account.</p>
        <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
        <p class="text" style="margin-top: 24px; font-size: 12px; color: #9ca3af;">If you did not request a password reset, no further action is required.</p>
    </div>
</body>
</html>
