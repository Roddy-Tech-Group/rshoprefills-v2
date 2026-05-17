<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Placed</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; color: #111827; margin: 0; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #f3f4f6; }
        .header { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 20px; }
        .text { font-size: 16px; line-height: 1.6; color: #4b5563; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">Order Placed Successfully!</div>
        <p class="text">Hi {{ $name }},</p>
        <p class="text">Thank you for your order! We are currently processing your purchase of digital services.</p>
        <p class="text">Order Number: <strong>#{{ $orderNumber }}</strong></p>
        <p class="text">Total Amount paid: <strong>{{ number_format($total, 2) }} {{ $currency }}</strong></p>
        <p class="text">Item(s) count: <strong>{{ $itemsCount }}</strong></p>
    </div>
</body>
</html>
