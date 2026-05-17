<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Refund Processed</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; color: #111827; margin: 0; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #f3f4f6; }
        .header { font-size: 24px; font-weight: bold; color: #f59e0b; margin-bottom: 20px; }
        .text { font-size: 16px; line-height: 1.6; color: #4b5563; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">Refund Processed</div>
        <p class="text">Hi {{ $name }},</p>
        <p class="text">We would like to inform you that a refund of <strong>{{ number_format($amount, 2) }} {{ $currency }}</strong> has been processed back to your wallet for Order <strong>#{{ $orderNumber }}</strong>.</p>
        <p class="text">Reason for Refund: <em>{{ $reason }}</em></p>
        <p class="text">You can view your updated wallet balance inside your RshopRefills dashboard.</p>
    </div>
</body>
</html>
