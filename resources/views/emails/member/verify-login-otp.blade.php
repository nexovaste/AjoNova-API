<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login Verification - Unity Co-op</title>
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

        /* Gradient Header */
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

        /* Branding Text - Kept at 23px / 6px spacing */
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

        /* Body - Left Aligned */
        .email-body {
            padding: 40px;
            text-align: left;
        }

        .email-body h2 {
            color: #0f172a;
            font-size: 24px;
            margin: 0 0 15px 0;
            font-weight: 700;
        }

        .email-body p {
            color: #475569;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* OTP Code Styling - Centralized */
        .otp-box-wrapper {
            text-align: center;
            margin: 30px 0;
        }

        .otp-container {
            background-color: #fdfbf7;
            border: 1px solid #e9d1a1;
            border-radius: 12px;
            padding: 25px;
            display: inline-block;
            min-width: 400px;
        }

        .otp-label {
            font-size: 11px;
            color: #8a6d3b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            display: block;
            font-weight: 700;
        }

        .otp-code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #2d5a27;
            margin: 0;
            font-family: 'Consolas', monospace;
        }

        .timer-notice {
            color: #c53030;
            font-size: 12px;
            font-weight: 600;
            margin-top: 12px;
            display: block;
        }

        .details-info {
            font-size: 13px;
            color: #718096;
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            border: 1px solid #edf2f7;
            line-height: 1.6;
        }

        .footer {
            padding: 30px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        /* MOBILE OPTIMIZATION */
        @media only screen and (max-width: 600px) {
            .email-header {
                padding: 25px 20px;
            }

            .header-title {
                font-size: 18px !important;
                letter-spacing: 2px !important;
            }

            .header-subtitle {
                font-size: 10px !important;
                letter-spacing: 2px !important;
            }

            .logo-cell {
                width: 55px;
            }

            .logo-cell img {
                width: 50px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .otp-code {
                font-size: 28px !important;
                letter-spacing: 4px !important;
            }

            .otp-container {
                min-width: 85%;
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
                            <a href="https://ibb.co/My4WHbZq"><img src="https://i.ibb.co/mrw1KmbQ/logo.png" alt="logo" border="0"></a>

                        </td>
                        <td class="title-cell">
                            <h1 class="header-title">Unity Cooperative</h1>
                            <span class="header-subtitle">Member Security Portal</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="email-body">
                <h2>Secure Your Login</h2>
                <p>Hello <strong>{{ $title }}. {{ $fullName }}</strong>,</p>
                <p>To access your member dashboard and cooperative records, please use the following security code:</p>

                <div class="otp-box-wrapper">
                    <div class="otp-container">
                        <span class="otp-label">Member Verification Code</span>
                        <h1 class="otp-code">{{ $otp }}</h1>
                        <span class="timer-notice">⏱ Valid for 10 minutes</span>
                    </div>
                </div>

                <p style="font-size: 14px; color: #64748b;">For your protection, never share this code with anyone. Unity Cooperative staff will never ask for your OTP over the phone or via email.</p>

                <div class="details-info">
                    Login requested from: <b>{{ $location }}</b><br>
                    Platform: <b>{{ $device }}</b>
                </div>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} <b>Unity Cooperative Society</b><br>
                Empowering Members • Building Community<br>
                Member Protection Services
            </div>

        </div>
    </div>
</body>

</html>