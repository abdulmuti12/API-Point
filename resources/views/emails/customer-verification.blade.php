<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - Casa Italia</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #8B4513, #A0522D);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .body {
            padding: 40px 30px;
            text-align: center;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0 0 16px;
            text-align: left;
        }
        .message {
            font-size: 15px;
            line-height: 1.7;
            color: #4a4a4a;
            margin: 0 0 24px;
            text-align: left;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #8B4513, #A0522D);
            color: white !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
        }
        .fallback-link {
            font-size: 12px;
            color: #868e96;
            word-break: break-all;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-top: 16px;
            text-align: left;
        }
        .warning {
            font-size: 13px;
            color: #c0392b;
            background: #fdf0ef;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 12px 16px;
            margin: 16px 0;
            text-align: left;
        }
        .footer {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 24px 30px;
            text-align: center;
            font-size: 13px;
            color: #868e96;
        }
        .footer strong {
            color: #8B4513;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Casa Italia Living</h1>
            <p>Email Verification</p>
        </div>
        <div class="body">
            <p class="greeting">Halo {{ $name }},</p>

            <p class="message">
                Terima kasih telah mendaftar di <strong>Casa Italia Living</strong>.
                Untuk mengaktifkan akun Anda, silakan klik tombol verifikasi di bawah ini:
            </p>

            <a href="{{ config('app.frontend_url') }}/verify-email?email={{ urlencode($email) }}&token={{ $token }}" class="button">
                Verifikasi Email Saya
            </a>

            <div class="fallback-link">
                Atau copy paste link berikut ke browser Anda:<br><br>
                {{ config('app.frontend_url') }}/verify-email?email={{ urlencode($email) }}&token={{ $token }}
            </div>

            <div class="warning">
                <strong>⚠️ Penting:</strong>
                Link verifikasi ini berlaku selama 24 jam. Jika Anda tidak merasa mendaftar di Casa Italia Living, abaikan email ini.
            </div>

            <p class="message">
                Terima kasih,<br>
                <strong>Tim Casa Italia Living</strong>
            </p>
        </div>
        <div class="footer">
            <p>Dicetak secara otomatis oleh <strong>Casa Italia Living</strong>.</p>
            <p>Jangan membalas email ini.</p>
        </div>
    </div>
</body>
</html>
