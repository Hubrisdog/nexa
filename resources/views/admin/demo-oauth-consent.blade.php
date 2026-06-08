<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Accounts - Consent Simulation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #090d16;
            color: #f9fafb;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .oauth-card {
            background-color: #111827;
            border: 1px solid #1f2937;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            padding: 32px;
            text-align: left;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .google-logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
            display: inline-flex;
            gap: 2px;
        }
        .google-logo span:nth-child(1) { color: #4285F4; }
        .google-logo span:nth-child(2) { color: #EA4335; }
        .google-logo span:nth-child(3) { color: #FBBC05; }
        .google-logo span:nth-child(4) { color: #4285F4; }
        .google-logo span:nth-child(5) { color: #34A853; }
        .google-logo span:nth-child(6) { color: #EA4335; }
        
        .simulation-badge {
            background-color: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.2);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        h1 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 8px;
            color: #f3f4f6;
        }
        .app-info {
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .scopes-list {
            background-color: rgba(31, 41, 55, 0.4);
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .scope-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13.5px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }
        .scope-item:last-child {
            margin-bottom: 0;
        }
        .scope-item i {
            color: #6366f1;
            margin-top: 3px;
        }
        .scope-desc {
            line-height: 1.4;
        }
        .disclaimer {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 28px;
        }
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .btn {
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-cancel {
            background-color: transparent;
            border: 1px solid #374151;
            color: #cbd5e1;
        }
        .btn-cancel:hover {
            background-color: #1f2937;
            border-color: #4b5563;
        }
        .btn-allow {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }
        .btn-allow:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(99, 102, 241, 0.35);
            opacity: 0.95;
        }
    </style>
</head>
<body>
    <div class="oauth-card">
        <div class="header">
            <div class="google-logo">
                <span>G</span><span>o</span><span>o</span><span>g</span><span>l</span><span>e</span>
            </div>
            <div>
                <span class="simulation-badge"><i class="fas fa-flask mr-1"></i> Sandbox Simulation</span>
            </div>
            <h1>Nexa wants to access your Google Account</h1>
            <div class="app-info">demo.admin@nexa-scheduler.com</div>
        </div>

        <div class="scopes-list">
            <div class="scope-item">
                <i class="fas fa-calendar-alt"></i>
                <div class="scope-desc">
                    <strong>Manage calendars</strong><br>
                    Create, update, and delete calendars that you have access to.
                </div>
            </div>
            <div class="scope-item">
                <i class="fas fa-calendar-check"></i>
                <div class="scope-desc">
                    <strong>View and edit events</strong><br>
                    Manage individual events on all your connected calendars.
                </div>
            </div>
            <div class="scope-item">
                <i class="fas fa-user"></i>
                <div class="scope-desc">
                    <strong>View profile info</strong><br>
                    Associate calendar events with your connected email address.
                </div>
            </div>
        </div>

        <div class="disclaimer">
            This is a sandboxed OAuth integration simulation. Nexa will store a mock OAuth connection for the demo tenant. No real credentials or calendar data will be transferred or requested.
        </div>

        <div class="actions">
            <a href="/admin/settings?sync=cancelled" class="btn btn-cancel">Cancel</a>
            <a href="/api/oauth/google/callback?code=mock-google-auth-code&state=mock-state" class="btn btn-allow">Allow & Connect</a>
        </div>
    </div>
</body>
</html>
