<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirection vers l'application...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        p {
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }
        .btn {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .status {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .status.success { background: rgba(72, 187, 120, 0.3); }
        .status.failed { background: rgba(245, 101, 101, 0.3); }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>Redirection en cours...</h1>
        <p>Vous allez etre redirige vers l'application SomeWhere</p>

        @if($status)
            <div class="status {{ $status === 'SUCCESSFUL' ? 'success' : ($status === 'FAILED' ? 'failed' : '') }}">
                @if($status === 'SUCCESSFUL')
                    Paiement reussi
                @elseif($status === 'FAILED')
                    Paiement echoue
                @else
                    Statut: {{ $status }}
                @endif
            </div>
        @endif

        <p style="margin-top: 2rem;">
            <a href="{{ $deepLink }}" class="btn">Ouvrir l'application</a>
        </p>
    </div>

    <script>
        // Auto-redirect to the app
        setTimeout(function() {
            window.location.href = "{{ $deepLink }}";
        }, 500);
    </script>
</body>
</html>
