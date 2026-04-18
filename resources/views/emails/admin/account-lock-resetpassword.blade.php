<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Locked - Unity Co-op</title>
    <style>
        /* General Reset */
        body, html {
            margin: 0; padding: 0; width: 100% !important;
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Dark Mode Protection */
        @media (prefers-color-scheme: dark) {
            .header-title { color: #ffffff !important; }
            .header-subtitle { color: #e9d1a1 !important; }
            .email-container { background-color: #ffffff !important; }
            .email-body h2, .email-body p { color: #0f172a !important; }
            .details-card { background-color: #fdfbf7 !important; }
        }

        .email-wrapper { width: 100%; background-color: #f1f5f9; padding: 20px 0; }

        .email-container {
            max-width: 600px; margin: 0 auto; background: #ffffff;
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;
        }

        /* Gradient Header - Matching your OTP exactly */
        .email-header {
            background: linear-gradient(135deg, #1e3a1a 0%, #2d5a27 100%);
            padding: 35px;
        }

        .header-table { width: 100%; border-collapse: collapse; }
        .logo-cell { width: 75px; vertical-align: middle; }
        .logo-cell img { width: 70px; height: auto; display: block; border-radius: 10px; }
        .title-cell { vertical-align: middle; padding-left: 15px; }

        /* Branding Text - Exactly 23px with 6px spacing */
        .header-title {
            color: #ffffff !important; font-size: 23px; font-weight: 800;
            letter-spacing: 6px; margin: 0; line-height: 1.1; text-transform: uppercase;
        }

        .header-subtitle {
            color: #e9d1a1 !important; font-size: 13px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 6px; margin-top: 2px; display: block;
        }

        /* Security Alert Banner */
        .security-banner {
            background-color: #fff5f5;
            border-bottom: 1px solid #fed7d7;
            padding: 12px 20px;
            color: #c53030;
            font-size: 13px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Body Content */
        .email-body { padding: 40px; text-align: left; }
        .email-body h2 { color: #0f172a; font-size: 24px; margin: 0 0 15px 0; font-weight: 700; }
        .email-body p { color: #475569; font-size: 16px; line-height: 1.6; margin-bottom: 20px; }

        /* Incident Details Card */
        .details-card {
            background-color: #fcf9f2; border: 1px solid #e9d1a1;
            border-radius: 12px; padding: 20px; margin: 25px 0;
        }
        .detail-row { font-size: 14px; margin-bottom: 8px; color: #5d4037; }
        .detail-label { font-weight: 700; color: #2d5a27; width: 80px; display: inline-block; }

        /* Button - Centered */
        .cta-wrapper { text-align: center; margin: 35px 0; }
        .button {
            background-color: #2d5a27; color: #ffffff !important;
            padding: 18px 35px; text-decoration: none; border-radius: 8px;
            font-weight: 700; display: inline-block;
            box-shadow: 0 4px 12px rgba(45, 90, 39, 0.2);
        }

        .timer-notice {
            color: #be123c; font-size: 12px; font-weight: 700;
            margin-top: 15px; display: block;
        }

        .footer {
            padding: 30px; font-size: 12px; color: #64748b;
            text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0;
        }

        /* MOBILE OPTIMIZATION */
        @media only screen and (max-width: 600px) {
            .email-header { padding: 25px 20px; }
            .header-title { font-size: 18px !important; letter-spacing: 2px !important; }
            .header-subtitle { font-size: 10px !important; letter-spacing: 2px !important; }
            .logo-cell { width: 55px; }
            .logo-cell img { width: 50px; }
            .email-body { padding: 30px 20px; }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-container">

            <div class="email-header">
                <table class="header-table" role="presentation">
                    <tr>
                        <td class="logo-cell">
                                                       <a href="https://ibb.co/My4WHbZq"><img src="https://i.ibb.co/mrw1KmbQ/logo.png" alt="logo" border="0"></a>

                        </td>
                        <td class="title-cell">
                            <h1 class="header-title">Unity Cooperative</h1>
                            <span class="header-subtitle">Security Notification</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="security-banner">
                Account Protection Locked
            </div>

            <div class="email-body">
                <h2>Administrative Access Restricted</h2>
                <p>Hello <strong>{{ $title }}. {{ $fullName }}</strong>,</p>
                <p>To safeguard the cooperative's data, your account has been temporarily locked following multiple unsuccessful login attempts.</p>

                <div class="details-card">
                    <div class="detail-row"><span class="detail-label">Device:</span> {{ $device }}</div>
                    <div class="detail-row"><span class="detail-label">Location:</span> {{ $location }}</div>
                    <div class="detail-row"><span class="detail-label">Browser:</span> {{ $browser}}</div>
                </div>

                <p>To regain access, please click the secure button below to verify your identity.</p>

                <div class="cta-wrapper">
                    <a href="{{ $url }}" class="button">Verify & Unlock Account</a>
                    <span class="timer-notice">⏱ This link is valid for 10 minutes</span>
                </div>

                <p style="font-size: 14px; color: #64748b; font-style: italic; text-align: center;">
                    If you did not perform this action, please contact the ICT Security Unit immediately.
                </p>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} <b>Unity Cooperative Society</b><br>
                Strength in Community & Growth
            </div>

        </div>
    </div>
</body>
</html>