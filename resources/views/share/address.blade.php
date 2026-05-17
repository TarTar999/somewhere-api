<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary Meta Tags -->
    <title>{{ $shareData['title'] }}</title>
    <meta name="title" content="{{ $shareData['title'] }}">
    <meta name="description" content="{{ $shareData['description'] }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ $shareData['type'] }}">
    <meta property="og:url" content="{{ $shareData['url'] }}">
    <meta property="og:title" content="{{ $shareData['title'] }}">
    <meta property="og:description" content="{{ $shareData['description'] }}">
    <meta property="og:image" content="{{ $shareData['image'] }}">
    <meta property="og:site_name" content="{{ $shareData['siteName'] }}">
    <meta property="og:locale" content="fr_FR">

    <!-- Twitter -->
    <meta name="twitter:card" content="{{ $shareData['twitterCard'] }}">
    <meta name="twitter:url" content="{{ $shareData['url'] }}">
    <meta name="twitter:title" content="{{ $shareData['twitterTitle'] }}">
    <meta name="twitter:description" content="{{ $shareData['twitterDescription'] }}">
    <meta name="twitter:image" content="{{ $shareData['image'] }}">

    <!-- Apple Smart App Banner (when app is on App Store) -->
    @if(!empty($config['stores']['ios']))
    <meta name="apple-itunes-app" content="app-id=YOUR_APP_ID, app-argument={{ $deepLink['primary'] }}">
    @endif

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ $config['logoUrl'] }}">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
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

        .sw-address {
            font-size: 28px;
            font-weight: 700;
            color: #4ade80;
            margin-bottom: 8px;
            font-family: monospace;
        }

        .address-details {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .coordinates {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            font-family: monospace;
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
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            margin-bottom: 12px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(74, 222, 128, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
        }

        .app-name {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 24px;
        }

        .loading {
            display: none;
            margin-top: 16px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #4ade80;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .store-buttons {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .store-btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($config['logoUrl'])
        <img src="{{ $config['logoUrl'] }}" alt="{{ $config['appName'] }}" class="logo">
        @endif

        <div class="card">
            <div class="sw-address">{{ $address->sw_address }}</div>

            <div class="address-details">
                @if($address->quarter)
                    {{ $address->quarter }}
                    @if($address->sub_quarter)
                        - {{ $address->sub_quarter }}
                    @endif
                    <br>
                @endif

                @if($address->street && $address->street->display_name)
                    {{ $address->street->display_name }}
                    <br>
                @endif

                @if($address->display_name && $address->display_name !== $address->quarter)
                    {{ $address->display_name }}
                @endif
            </div>

            <div class="coordinates">
                {{ number_format($address->latitude, 6) }}, {{ number_format($address->longitude, 6) }}
            </div>
        </div>

        <a href="#" id="openAppBtn" class="btn">
            Ouvrir dans SomeWhere
        </a>

        <a href="https://www.google.com/maps?q={{ $address->latitude }},{{ $address->longitude }}"
           target="_blank"
           class="btn btn-secondary">
            Voir sur Google Maps
        </a>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 8px; font-size: 14px; color: rgba(255,255,255,0.7);">
                Ouverture de l'application...
            </p>
        </div>

        @if($deepLink['mode'] === 'production' && (!empty($config['stores']['ios']) || !empty($config['stores']['android'])))
        <div class="store-buttons" id="storeButtons" style="display: none;">
            @if(!empty($config['stores']['ios']))
            <a href="{{ $config['stores']['ios'] }}" class="store-btn">
                App Store
            </a>
            @endif
            @if(!empty($config['stores']['android']))
            <a href="{{ $config['stores']['android'] }}" class="store-btn">
                Google Play
            </a>
            @endif
        </div>
        @endif

        <p class="app-name">{{ $config['appName'] }} - Votre adresse unique</p>
    </div>

    <script>
        const deepLinkConfig = @json($deepLink);
        const openAppBtn = document.getElementById('openAppBtn');
        const loading = document.getElementById('loading');
        const storeButtons = document.getElementById('storeButtons');

        function openApp() {
            loading.classList.add('show');

            const deepLinkUrl = deepLinkConfig.primary;

            // Try to open the app
            const start = Date.now();

            // Create a hidden iframe to try the deep link
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = deepLinkUrl;
            document.body.appendChild(iframe);

            // Also try window.location for better compatibility
            setTimeout(() => {
                window.location.href = deepLinkUrl;
            }, 100);

            // Check if the app opened (page will be hidden)
            setTimeout(() => {
                loading.classList.remove('show');

                // If we're still here after 2.5 seconds, the app probably didn't open
                if (Date.now() - start > 2000 && document.visibilityState !== 'hidden') {
                    if (deepLinkConfig.mode === 'production' && storeButtons) {
                        storeButtons.style.display = 'flex';
                    } else if (deepLinkConfig.mode === 'development') {
                        alert('Assurez-vous que Expo Go est installé et que le serveur de développement est en cours d\'exécution.');
                    }
                }

                document.body.removeChild(iframe);
            }, 2500);
        }

        openAppBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openApp();
        });

        // Auto-open app on page load (optional - uncomment to enable)
        // setTimeout(openApp, 500);
    </script>
</body>
</html>
