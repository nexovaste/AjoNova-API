<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Unity Cooperative</title>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100% !important;
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        @media (prefers-color-scheme: dark) {
            .header-title {
                color: #ffffff !important;
            }

            .header-subtitle {
                color: #e9d1a1 !important;
            }

            .email-container {
                background-color: #ffffff !important;
            }

            .email-body h2,
            .email-body p {
                color: #0f172a !important;
            }

            .cred-box {
                background-color: #fdfbf7 !important;
            }
        }

        .email-wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 20px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .email-header {
            background: linear-gradient(135deg, #1e3a1a 0%, #2d5a27 100%);
            padding: 35px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 75px;
            vertical-align: middle;
        }

        .logo-cell img {
            width: 70px;
            height: auto;
            display: block;
            border-radius: 10px;
        }

        .title-cell {
            vertical-align: middle;
            padding-left: 15px;
        }

        .header-title {
            color: #ffffff !important;
            font-size: 23px;
            font-weight: 800;
            letter-spacing: 6px;
            margin: 0;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .header-subtitle {
            color: #e9d1a1 !important;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 6px;
            margin-top: 2px;
            display: block;
        }

        .email-body {
            padding: 40px;
            text-align: left;
        }

        .email-body h2 {
            color: #0f172a;
            font-size: 26px;
            margin: 0 0 15px 0;
            font-weight: 800;
        }

        .salutation {
            color: #2d5a27;
            font-weight: 800;
            font-size: 18px;
            margin-bottom: 10px;
            display: block;
        }

        .email-body p {
            color: #475569;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .cred-wrapper {
            text-align: center;
            margin: 30px 0;
        }

        .cred-box {
            background-color: #fdfbf7;
            border: 1px dashed #e9d1a1;
            border-radius: 12px;
            padding: 25px;
            display: inline-block;
            min-width: 350px;
        }

        .cred-label {
            font-size: 11px;
            color: #8a6d3b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            display: block;
            font-weight: 700;
        }

        .cred-item {
            font-size: 16px;
            color: #1e3a1a;
            margin: 8px 0;
        }

        .cred-value {
            font-size: 24px;
            font-weight: 800;
            color: #2d5a27;
            font-family: 'Consolas', monospace;
            letter-spacing: 1px;
        }

        .btn-wrapper {
            text-align: center;
            margin-top: 25px;
        }

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

        .footer {
            padding: 30px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        @media only screen and (max-width: 600px) {
            .header-title {
                font-size: 18px !important;
                letter-spacing: 2px !important;
            }

            .header-subtitle {
                font-size: 10px !important;
                letter-spacing: 2px !important;
            }

            .cred-box {
                min-width: 90%;
            }
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
                            <a href="#"><img src="https://raw.githubusercontent.com/Xisco0/cooperative-assets/main/logo.png" alt="logo" border="0"></a>

                        </td>
                        <td class="title-cell">
                            <h1 class="header-title">Unity Cooperative</h1>
                            <span class="header-subtitle">Member Onboarding</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="email-body">
                <span class="salutation">Welcome Aboard!</span>
                <h2>Hello {{ $title }}. {{ $fullName }},</h2>
                <p>We are delighted to welcome you to <strong>Unity Cooperative Society</strong>. Your membership has been successfully activated, giving you full access to our financial services and community benefits.</p>
                <p>To get started, please log in with the <b>email address</b> you provided during registration and use the default password generated for your account:</p>

                <div class="cred-wrapper">
                    <div class="cred-box">
                        <span class="cred-label">Your Default Password</span>
                        <div class="cred-item"><span class="cred-value">{{ $surname }}123</span></div>

                        <div class="btn-wrapper">
                            <a href="http://localhost/unity-cooperative.com/login" class="btn">
                                <span class="btn-text">Log In to Portal</span>
                            </a>
                        </div>
                    </div>
                </div>

                <p style="font-size: 15px; color: #64748b; border-left: 4px solid #e9d1a1; padding-left: 15px;">
                    <strong>Important:</strong> For your security, you will be required to change this password immediately after your first successful login.
                </p>

                <p style="margin-top: 30px;">
                    Best regards,<br>
                    <strong>The Unity Membership Team</strong>
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