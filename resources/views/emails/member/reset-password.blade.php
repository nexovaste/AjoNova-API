<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Password Reset - Unity Co-op</title>
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
        }

        .email-wrapper { width: 100%; background-color: #f1f5f9; padding: 20px 0; }

        .email-container {
            max-width: 600px; margin: 0 auto; background: #ffffff;
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;
        }

        /* Gradient Header */
        .email-header {
            background: linear-gradient(135deg, #1e3a1a 0%, #2d5a27 100%);
            padding: 35px;
        }

        .header-table { width: 100%; border-collapse: collapse; }
        .logo-cell { width: 75px; vertical-align: middle; }
        .logo-cell img { width: 70px; height: auto; display: block; border-radius: 10px; }
        .title-cell { vertical-align: middle; padding-left: 15px; }

        /* Branding Text - 23px with 6px spacing */
        .header-title {
            color: #ffffff !important; font-size: 23px; font-weight: 800;
            letter-spacing: 6px; margin: 0; line-height: 1.1; text-transform: uppercase;
        }

        .header-subtitle {
            color: #e9d1a1 !important; font-size: 13px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 6px; margin-top: 2px; display: block;
        }

        /* Body - Left Aligned Text */
        .email-body { padding: 40px; text-align: left; }
        .email-body h2 { color: #0f172a; font-size: 24px; margin: 0 0 15px 0; font-weight: 700; }
        .salutation { color: #2d5a27; font-weight: 800; margin-bottom: 8px; display: block; }
        .email-body p { color: #475569; font-size: 16px; line-height: 1.6; margin-bottom: 24px; }

        /* Button - Centralized */
        .btn-wrapper { text-align: center; margin: 35px 0; }
        .btn {
            display: inline-block;
            background-color: #2d5a27 !important;
            padding: 16px 40px;
            border-radius: 8px;
            text-decoration: none !important;
            box-shadow: 0 4px 12px rgba(45, 90, 39, 0.2);
        }
        .btn-text {
            color: #ffffff !important;
            font-weight: 700;
            font-size: 16px;
        }

        .timer-notice {
            color: #c53030; font-size: 12px; font-weight: 700;
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
                            <img src="https://i.postimg.cc/Pr5NqYc2/logo.png" alt="Logo">
                        </td>
                        <td class="title-cell">
                            <h1 class="header-title">Unity Cooperative</h1>
                            <span class="header-subtitle">Member Security</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="email-body">
                <span class="salutation">Dear, {{ $title }}. {{ $fullName }},</span>
                <h2>Reset Your Password</h2>
                <p>We received a request to reset the password for your Unity Cooperative member account. To ensure your financial records remain secure, please click the button below to create a new password.</p>

                <div class="btn-wrapper">
                    <a href="{{ $url }}" class="btn">
                        <span class="btn-text">Set New Password</span>
                    </a>
                    <span class="timer-notice">⏱ This link is valid for 10 minutes</span>
                </div>

                <p style="font-size: 14px; color: #64748b; font-style: italic;">
                    If you did not make this request, your account is still secure. You can safely ignore this email.
                </p>
                
                <p style="margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                    Kind regards,<br>
                    <strong>Unity Member Services</strong>
                </p>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} <b>Unity Cooperative Society</b><br>
                Strength in Community & Growth<br>
                Member Protection Services
            </div>

        </div>
    </div>
</body>
</html>