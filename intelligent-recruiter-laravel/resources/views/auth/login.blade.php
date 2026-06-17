<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — Intelligent Recruiter</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: #ffffff;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* ── TOP BAR ── */
        .topbar {
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-icon {
            width: 26px;
            height: 26px;
            background: #0f172a;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-icon svg { width: 14px; height: 14px; }

        .brand-name {
            font-family: 'DM Serif Display', serif;
            font-size: 1rem;
            color: #0f172a;
            letter-spacing: -0.01em;
        }

        /* ── CENTER CARD ── */
        .center {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .card {
            width: 100%;
            max-width: 360px;
            text-align: center;
        }

        .card h1 {
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: #0f172a;
            margin-bottom: 0.4rem;
        }

        .card .sub {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 2.25rem;
            line-height: 1.5;
        }

        /* ── GOOGLE BUTTON ── */
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            color: #0f172a;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.12s, border-color 0.12s, box-shadow 0.12s;
        }

        .btn-google:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .btn-google svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        /* ── DIVIDER ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.5rem 0;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        /* ── FOOTER ── */
        .footer {
            padding: 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="brand-icon">
            <svg viewBox="0 0 20 20" fill="none">
                <path d="M10 2L13.5 7H17L14 11L15.5 16L10 13L4.5 16L6 11L3 7H6.5L10 2Z" fill="white" fill-opacity="0.95"/>
            </svg>
        </div>
        <span class="brand-name">Intelligent Recruiter</span>
    </div>

    <div class="center">
        <div class="card">
            <h1>Welcome back</h1>
            <p class="sub">Sign in to access your candidate pipeline</p>

            <a href="{{ route('auth.google') }}" class="btn-google">
                <!-- Google "G" logo -->
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continue with Google
            </a>
        </div>
    </div>

    <div class="footer">
        By signing in you agree to use this app for recruitment purposes only.
    </div>

</body>
</html>
