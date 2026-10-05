<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang di Casa Italia Living</title>
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
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0 0 16px;
        }
        .message {
            font-size: 15px;
            line-height: 1.7;
            color: #4a4a4a;
            margin: 0 0 24px;
        }
        .credentials-box {
            background: #fff8f0;
            border: 1px solid #f0d9b5;
            border-left: 4px solid #8B4513;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .credentials-title {
            font-size: 14px;
            font-weight: 600;
            color: #8B4513;
            margin: 0 0 12px;
            text-transform: uppercase;
        }
        .credential-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }
        .credential-label {
            color: #666;
            font-weight: 500;
        }
        .credential-value {
            color: #1a1a2e;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            background: #f0ede9;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .warning {
            font-size: 13px;
            color: #c0392b;
            background: #fdf0ef;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 12px 16px;
            margin: 16px 0;
        }
        .warning strong {
            display: block;
            margin-bottom: 4px;
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
            <p>Welcome to our community</p>
        </div>
        <div class="body">
            <p class="greeting">Halo {{ $fullName }},</p>

            <p class="message">
                Selamat! Akun Anda telah berhasil terdaftar di <strong>Casa Italia Living</strong>.
                Sekarang Anda bisa menikmati berbagai produk eksklusif kami dari seluruh Italia.
            </p>

            <div class="credentials-box">
                <div class="credentials-title">Informasi Login Anda</div>
                <div class="credential-row">
                    <span class="credential-label">Email</span>
                    <span class="credential-value">{{ $email }}</span>
                </div>
                <div class="credential-row">
                    <span class="credential-label">Password</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>

            <div class="warning">
                <strong>⚠️ Penting:</strong>
                Simpan password ini dengan aman. Anda akan membutuhkan password ini untuk masuk ke akun Anda di website Casa Italia Living.
            </div>

            <p class="message">
                Jika Anda mengalami kendala saat login, silakan hubungi tim support kami melalui email <a href="mailto:info@casaitalia-living.com">info@casaitalia-living.com</a> atau WhatsApp.
            </p>

            <p class="message">
                Terima kasih telah bergabung bersama kami.<br>
                Hormat kami,<br>
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
