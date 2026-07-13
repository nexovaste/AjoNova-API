<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Unity Co-op</title>
    <style>
        /* General Reset */
        body, html {
            margin: 0;
            padding: 0;
            width: 100% !important;
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 40px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        /* Executive Gradient Header */
        .email-header {
            background: linear-gradient(135deg, #1e3a1a 0%, #2d5a27 100%);
            padding: 40px;
            text-align: left;
        }

        /* Logo and Title Alignment Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 85px; /* Fixed width for alignment */
            vertical-align: middle;
        }

        .logo-cell img {
            width: 80px; /* Increased Size */
            height: auto;
            display: block;
            border-radius: 10px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        }

        .title-cell {
            vertical-align: middle;
            padding-left: 20px;
        }

        .header-title {
            color: #ffffff;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
            line-height: 1;
        }

        .header-subtitle {
            color: #e9d1a1; /* Gold tint from logo */
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
            display: block;
        }

        /* Body */
        .email-body {
            padding: 50px 45px;
            text-align: left;
        }

        .welcome-tag {
            color: #2d5a27;
            font-weight: 800;
            margin-bottom: 12px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1.5px;
            display: block;
        }

        .email-body h2 {
            color: #0f172a;
            font-size: 28px;
            margin: 0 0 20px 0;
            font-weight: 700;
        }

        .email-body p {
            color: #475569;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        /* Professional Password Card */
        .password-container {
            background-color: #fdfbf7;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 35px 0;
            border: 1px solid #e9d1a1;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .password-label {
            font-size: 11px;
            color: #8a6d3b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            display: block;
            font-weight: 700;
        }

        .password-text {
            font-family: 'Consolas', 'Monaco', monospace;
            font-weight: 700;
            color: #1e3a1a;
            font-size: 22px;
            letter-spacing: 4px;
            background: #ffffff;
            border-radius: 6px;
            border: 1px solid #f1f5f9;
            display: inline-block;
        }

        /* Polished Button */
        .btn-container {
            text-align: center;
            margin: 35px 0;
        }

        .btn {
            display: inline-block;
            background-color: #2d5a27 !important;
            padding: 20px 45px;
            border-radius: 10px;
            text-decoration: none !important;
            box-shadow: 0 10px 20px rgba(45, 90, 39, 0.2);
        }

        .btn-text {
            color: #ffffff !important;
            font-weight: 700;
            font-size: 16px;
        }

        /* Security Alert */
        .security-alert {
            background-color: #fff1f2;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #e11d48;
            margin-bottom: 25px;
        }

        .security-alert strong {
            color: #9f1239;
            display: block;
            margin-bottom: 4px;
        }

        .security-alert p {
            color: #9f1239;
            font-size: 14px;
            margin: 0;
            line-height: 1.4;
        }

        /* Footer */
        .email-footer {
            padding: 40px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .footer-brand {
            color: #2d5a27;
            font-weight: 700;
            font-size: 14px;
        }

        /* Mobile Adjustments */
        @media only screen and (max-width: 600px) {
            .email-header { padding: 30px 20px; }
            .header-title { font-size: 22px; }
            .logo-cell img { width: 65px; }
            .email-body { padding: 35px 25px; }
            .password-text { font-size: 18px; letter-spacing: 2px; }
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
                            <a href="#"><img src="https://raw.githubusercontent.com/Xisco0/cooperative-assets/main/logo.png" alt="Unity Co-op Logo" border="0"></a>

                        </td>
                        <td class="title-cell">
                            <h1 class="header-title">Unity Cooperative</h1>
                            <span class="header-subtitle">Administrative Portal</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="email-body">
                <span class="welcome-tag">Onboarding Notice</span>
                <h2>Account Successfully Prepared</h2>

                <p>Hello <strong>{{ $title }}. {{ $fullName }}</strong>,</p>

                <p>Your administrative credentials for the <strong>Unity Cooperative Society Portal</strong> have been generated. You now have authorized access to manage society operations and financial records.</p>

                <div class="password-container">
                    <span class="password-label">Temporary Admin Password</span>
                    <span class="password-text">{{ $password }}</span>
                </div>

                <div class="btn-container">
                    <a href="" class="btn">
                        <span class="btn-text">Log In to Dashboard</span>
                    </a>
                </div>

                <div class="security-alert">
                    <strong>Mandatory Security Action</strong>
                    <p>For the protection of society data, you must establish a new personal password immediately upon your first successful login.</p>
                </div>

                <p style="text-align: center; color: #64748b;">Welcome to the team! For technical assistance, please contact the ICT Support Directorate.</p>
            </div>

            <div class="email-footer">
                <span class="footer-brand">Unity Cooperative Society</span><br>
                Strength in Community & Growth<br><br>
                &copy; {{ date('Y') }} Central Admin Office. All Rights Reserved.<br>
                <span style="opacity: 0.6;">This is an automated system notification. Please do not reply.</span>
            </div>
        </div>
    </div>
</body>

</html>