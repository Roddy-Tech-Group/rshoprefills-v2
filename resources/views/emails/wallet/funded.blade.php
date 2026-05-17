<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wallet Funded</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; color: #111827; margin: 0; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #f3f4f6; }
        .header { font-size: 24px; font-weight: bold; color: #10b981; margin-bottom: 20px; }
        .text { font-size: 16px; line-height: 1.6; color: #4b5563; }
        .meta { font-size: 14px; color: #9ca3af; margin-top: 24px; border-top: 1px solid #f3f4f6; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">Wallet Funded Successfully!</div>
        <p class="text">Hi {{ $name }},</p>
        <p class="text">Your wallet has been funded with <strong>{{ number_format($amount, 2) }}
        <p class="text">New Wallet Balance: <strong>{{ number_format($balanceAfter, 2) }} {{ $currency }}</strong></p>
        <div class="meta">
            Reference: {{ $reference }}
        </div>
    </div>
</body>
</html>
