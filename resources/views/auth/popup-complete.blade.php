<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Signing you in...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #0c1a36; color: #ffffff; }
        .spinner { width: 32px; height: 32px; border: 3px solid rgba(255,255,255,0.2); border-top-color: #ffffff; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { margin-top: 16px; font-size: 14px; color: rgba(255,255,255,0.7); }
    </style>
</head>
<body>
    <div style="text-align: center;">
        <div class="spinner" style="margin: 0 auto;"></div>
        <p>{{ auth()->check() ? 'Signed in. Closing this window...' : 'Sign-in cancelled. Closing this window...' }}</p>
    </div>
    <script>
        (function () {
            var status  = {{ auth()->check() ? 'true' : 'false' }} ? 'success' : 'cancelled';
            var payload = { source: 'rshop-google-oauth', status: status };
            try {
                if (window.opener && ! window.opener.closed) {
                    window.opener.postMessage(payload, window.location.origin);
                }
            } catch (_) {}
            // Give the parent a beat to receive the message, then close. If we
            // can't close (popup blockers, opened in a real tab), navigate to
            // the dashboard / login so the user isn't stuck on this spinner.
            setTimeout(function () {
                try { window.close(); } catch (_) {}
                setTimeout(function () {
                    window.location.href = status === 'success' ? '{{ route('dashboard') }}' : '{{ route('login') }}';
                }, 400);
            }, 250);
        })();
    </script>
</body>
</html>
