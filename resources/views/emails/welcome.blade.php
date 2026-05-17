<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to RshopRefills</title>
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
        <div class="header">RshopRefills</div>
        <p class="text">Hi {{ $name }},</p>
        @if($isGoogleAuth)
            <p class="text">Welcome to RshopRefills! We're excited to have you on board. Your account was securely linked via Google Sign-In.</p>
        @else
            <p class="text">Welcome to RshopRefills! We're excited to have you on board. Start ordering eSIMs, Gift Cards, and Refills seamlessly.</p>
        @endif
        <a href="{{ url('/dashboard') }}" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>
