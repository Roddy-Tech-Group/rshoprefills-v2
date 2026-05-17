<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Order Alert</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; color: #111827; margin: 0; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #f3f4f6; }
        .header { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .alert-critical { color: #dc2626; }
        .alert-normal { color: #2563eb; }
        .text { font-size: 16px; line-height: 1.6; color: #4b5563; }
        .badge { display: inline-block; padding: 6px 12px; background-color: #fecaca; color: #dc2626; border-radius: 4px; font-weight: 600; font-size: 14px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="card">
        @if($isLargeTransaction)
            <div class="header alert-critical">CRITICAL: Large Transaction Detected!</div>
            <div class="badge">Suspicious Activity Threshold Tripped</div>
        @else
            <div class="header alert-normal">Admin Notification: New Order Placed</div>
        @endif
        <p class="text">A new Refill Order has been recorded in the platform database.</p>
        <p class="text">Order Number: <strong>#{{ $orderNumber }}</strong></p>
        <p class="text">Customer: <strong>{{ $customerName }}</strong> ({{ $customerEmail }})</p>
        <p class="text">Amount: <strong>{{ number_format($totalAmount, 2) }} {{ $currency }}</strong></p>
    </div>
</body>
</html>
