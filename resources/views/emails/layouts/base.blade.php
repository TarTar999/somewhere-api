<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', config('documents.company.brand'))</title>
    <style>
        /* Reset */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Base Styles */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            background-color: #F0F1F5;
            color: #16171B;
            line-height: 1.6;
        }

        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
        }

        /* Header */
        .email-header {
            background-color: #0F0F0F;
            padding: 24px 32px;
            text-align: center;
        }
        .email-header img {
            height: 40px;
            width: auto;
        }

        /* Body */
        .email-body {
            padding: 32px;
        }

        /* Footer */
        .email-footer {
            background-color: #F0F1F5;
            padding: 24px 32px;
            text-align: center;
            font-size: 12px;
            color: #4A4D58;
        }
        .email-footer a {
            color: #4A4D58;
            text-decoration: underline;
        }

        /* Typography */
        h1 {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #16171B;
            margin: 0 0 16px 0;
        }
        h2 {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: #16171B;
            margin: 0 0 12px 0;
        }
        p {
            margin: 0 0 16px 0;
            color: #2B2D35;
        }
        .text-muted {
            color: #4A4D58;
        }
        .text-small {
            font-size: 14px;
        }

        /* Button */
        .btn {
            display: inline-block;
            background-color: #0F0F0F;
            color: #FFFFFF !important;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
        }
        .btn:hover {
            background-color: #2B2D35;
        }
        .btn-outline {
            background-color: transparent;
            color: #0F0F0F !important;
            border: 2px solid #0F0F0F;
        }
        .btn-success {
            background-color: #4ADE80;
            color: #0F0F0F !important;
        }

        /* Card */
        .card {
            background-color: #F8F9FA;
            border-radius: 8px;
            padding: 20px;
            margin: 16px 0;
        }
        .card-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #16171B;
        }

        /* Info Box */
        .info-box {
            background-color: #EBF5FF;
            border-left: 4px solid #3B82F6;
            padding: 16px;
            margin: 16px 0;
            border-radius: 0 8px 8px 0;
        }
        .info-box.success {
            background-color: #ECFDF5;
            border-left-color: #4ADE80;
        }
        .info-box.warning {
            background-color: #FFFBEB;
            border-left-color: #FACC15;
        }
        .info-box.error {
            background-color: #FEF2F2;
            border-left-color: #EF4444;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        .data-table th {
            background-color: #F8F9FA;
            font-weight: 600;
            color: #4A4D58;
            font-size: 12px;
            text-transform: uppercase;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background-color: #ECFDF5;
            color: #059669;
        }
        .badge-warning {
            background-color: #FFFBEB;
            color: #D97706;
        }
        .badge-error {
            background-color: #FEF2F2;
            color: #DC2626;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #E5E7EB;
            margin: 24px 0;
        }

        /* Social Links */
        .social-links {
            margin-top: 16px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 8px;
        }
        .social-links img {
            width: 24px;
            height: 24px;
            opacity: 0.7;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            .email-body {
                padding: 24px 16px;
            }
            .email-header {
                padding: 20px 16px;
            }
            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F0F1F5; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" class="email-container" width="600" cellspacing="0" cellpadding="0" style="border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td class="email-header">
                            <img src="{{ asset('images/logo-somewhere-white.png') }}" alt="{{ config('documents.company.brand') }}">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="email-body">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="email-footer">
                            @yield('footer')

                            <hr class="divider">

                            <p style="margin-bottom: 8px;">
                                <strong>{{ config('documents.company.brand') }}</strong><br>
                                {{ config('documents.company.name') }}
                            </p>
                            <p style="margin-bottom: 8px;">
                                {{ config('documents.company.address') }}<br>
                                {{ config('documents.company.phone') }}
                            </p>
                            <p>
                                <a href="mailto:{{ config('documents.company.email') }}">{{ config('documents.company.email') }}</a>
                            </p>

                            @hasSection('unsubscribe')
                                <p style="margin-top: 16px;">
                                    <a href="@yield('unsubscribe')">Se désabonner</a>
                                </p>
                            @endif

                            <p class="text-small" style="margin-top: 16px; color: #9EA3B3;">
                                &copy; {{ date('Y') }} {{ config('documents.company.brand') }}. Tous droits réservés.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
