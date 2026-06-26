<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fulfillment Failed</title>
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .alert { background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin-bottom: 20px; }
        .log-box { background-color: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        th { color: #6b7280; font-weight: normal; }
    </style>
</head>
<body>
    <div class="container">
        <h2>CRITICAL: Fulfillment Failure</h2>
        
        <div class="alert">
            <strong>Action Required:</strong> A fulfillment attempt for Order #{{ $orderNumber }} has failed. The system requires manual intervention to either retry the fulfillment or refund the customer.
        </div>

        <h3>Order Details</h3>
        <table>
            <tr>
                <th>Order ID:</th>
                <td>{{ $orderNumber }}</td>
            </tr>
            <tr>
                <th>Item ID:</th>
                <td>{{ $item->id }}</td>
            </tr>
            <tr>
                <th>Product:</th>
                <td>{{ $item->product_name }}</td>
            </tr>
            <tr>
                <th>Provider:</th>
                <td>{{ $item->provider_name }}</td>
            </tr>
            <tr>
                <th>Reason Logged:</th>
                <td>{{ $reason }}</td>
            </tr>
        </table>

        <h3>Provider API Error Log</h3>
        <p>The following response was returned by the fulfillment provider:</p>
        <div class="log-box">{{ $apiErrorLog }}</div>
        
        <p>
            Please check the <a href="{{ config('app.url') }}/admin/orders/{{ $item->order_id }}">Admin Dashboard</a> to resolve this order.
        </p>
    </div>
</body>
</html>
