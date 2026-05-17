<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Adresse non trouvée - {{ $config['appName'] }}</title>
    <meta name="description" content="{{ $config['appDescription'] }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Adresse non trouvée - {{ $config['appName'] }}">
    <meta property="og:description" content="{{ $config['appDescription'] }}">
    <meta property="og:image" content="{{ $config['logoUrl'] }}">
    <meta property="og:site_name" content="{{ $config['appName'] }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ $config['logoUrl'] }}">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #fff;
        }

        .container {
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
            border-radius: 16px;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 32px 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 12px;
        }

        p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            background: #4ade80;
            color: #1a1a2e;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .app-name {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($config['logoUrl'])
        <img src="{{ $config['logoUrl'] }}" alt="{{ $config['appName'] }}" class="logo">
        @endif

        <div class="card">
            <div class="icon">🔍</div>
            <h1>Adresse non trouvée</h1>
            <p>
                Cette adresse n'existe pas ou a été supprimée.
                <br><br>
                Vérifiez le lien ou recherchez une nouvelle adresse dans l'application.
            </p>
        </div>

        @if(!empty($config['stores']['ios']) || !empty($config['stores']['android']))
        <a href="{{ $config['stores']['android'] ?: $config['stores']['ios'] }}" class="btn">
            Télécharger SomeWhere
        </a>
        @endif

        <p class="app-name">{{ $config['appName'] }} - Votre adresse unique</p>
    </div>
</body>
</html>
