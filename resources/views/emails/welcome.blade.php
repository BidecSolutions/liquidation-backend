<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Ma3rood</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to Ma3rood! 🎉</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->name }},</h2>
        
        <p>Welcome to Ma3rood! We're excited to have you join our marketplace community.</p>
        
        <p>Here's what you can do with your new account:</p>
        
        <ul>
            <li>🛍️ Browse and bid on amazing items</li>
            <li>📦 List your own items for sale</li>
            <li>💰 Make offers on listings</li>
            <li>👀 Watch items you're interested in</li>
            <li>📱 Get notified about bids and offers</li>
        </ul>
        
        <p>Ready to get started? Click the button below to explore our marketplace:</p>
        
        <a href="{{ config('app.frontend_url') }}" class="button">Start Browsing</a>
        
        <p>If you have any questions or need help, don't hesitate to contact our support team.</p>
        
        <p>Happy bidding!</p>
        
        <p>Best regards,<br>The Ma3rood Team</p>
    </div>
    
    <div class="footer">
        <p>This email was sent to {{ $user->email }}</p>
        <p>&copy; {{ date('Y') }} Ma3rood. All rights reserved.</p>
    </div>
</body>
</html>
