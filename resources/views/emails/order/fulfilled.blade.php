<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Digital Voucher Ready</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; color: #111827; margin: 0; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #f3f4f6; }
        .header { font-size: 24px; font-weight: bold; color: #10b981; margin-bottom: 20px; }
        .text { font-size: 16px; line-height: 1.6; color: #4b5563; }
        .voucher-box { background-color: #f3f4f6; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 18px; text-align: center; margin: 24px 0; border: 1px dashed #d1d5db; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">Your Refill/Voucher is Ready!</div>
        <p class="text">Hi {{ $name }},</p>
        <p class="text">The digital product for Order <strong>#{{ $orderNumber }}</strong> has been processed successfully.</p>
        <p class="text">Product: <strong>{{ $productName }}</strong></p>
        <div class="voucher-box">
            Voucher / Code: <strong>{{ $voucherCode }}</strong>
            @if($pinCode)
                <br>PIN: <strong>{{ $pinCode }}</strong>
            @endif
        </div>
        <p class="text">Thank you for choosing RshopRefills!</p>
    </div>
</body>
</html>
