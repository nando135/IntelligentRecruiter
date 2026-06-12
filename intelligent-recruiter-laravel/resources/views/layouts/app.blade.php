<!DOCTYPE html>
<html lang="en">
<head>
    <title>Intelligent Recruiter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink:       #0f1623;
            --ink-muted: #64748b;
            --ink-faint: #94a3b8;
            --surface:   #f8f9fc;
            --card:      #ffffff;
            --border:    #e4e9f0;
            --accent:    #1d4ed8;
            --accent-lt: #eff4ff;
            --accent-glow: rgba(29,78,216,0.12);
            --success:   #059669;
            --warn:      #d97706;
            --danger:    #dc2626;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            color: var(--ink);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── NAV ── */
        .nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            height: 62px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
            color: var(--ink);
        }

        .nav-brand-icon {
            width: 28px;
            height: 28px;
            background: var(--accent);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-brand-icon svg { width: 15px; height: 15px; }

        .nav-brand-name {
            font-family: 'DM Serif Display', serif;
            font-size: 1.1rem;
            letter-spacing: -0.01em;
            color: var(--ink);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .nav-link {
            font-size: 0.825rem;
            font-weight: 500;
            color: var(--ink-muted);
            text-decoration: none;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            transition: color 0.15s, background 0.15s;
            letter-spacing: 0.01em;
        }

        .nav-link:hover {
            color: var(--ink);
            background: var(--surface);
        }

        .nav-link.active {
            color: var(--accent);
            background: var(--accent-lt);
        }

        .nav-upload {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.825rem;
            font-weight: 600;
            color: #fff;
            background: var(--accent);
            text-decoration: none;
            padding: 0.5rem 1.1rem;
            border-radius: 9px;
            margin-left: 0.75rem;
            letter-spacing: 0.01em;
            transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
            box-shadow: 0 1px 3px rgba(29,78,216,0.25), 0 0 0 0 var(--accent-glow);
        }

        .nav-upload:hover {
            background: #1e40af;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(29,78,216,0.3);
        }

        .nav-upload:active { transform: translateY(0); }

        .nav-upload svg { width: 14px; height: 14px; }

        /* ── MAIN CONTENT ── */
        .main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2.5rem 2rem 4rem;
        }

        /* ── ALERTS ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            border-radius: 10px;
            font-size: 0.855rem;
            font-weight: 450;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-icon {
            flex-shrink: 0;
            width: 16px;
            height: 16px;
            margin-top: 1px;
        }

        /* ── CONTENT SLOT ── */
        .content-wrapper {
            animation: fadeUp 0.3s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── SCROLLBAR ── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--ink-faint); }
    </style>
</head>

<body>

    <!-- ── NAVIGATION ── -->
    <nav class="nav">
        <div class="nav-inner">

            <!-- Brand -->
            <a href="{{ route('candidates.index') }}" class="nav-brand">
                <div class="nav-brand-icon">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2L13.5 7H17L14 11L15.5 16L10 13L4.5 16L6 11L3 7H6.5L10 2Z" fill="white" fill-opacity="0.95"/>
                    </svg>
                </div>
                <span class="nav-brand-name">Intelligent Recruiter</span>
            </a>

            <!-- Links -->
            <div class="nav-links">
                <a href="{{ route('candidates.index') }}" class="nav-link">Candidates</a>
                <a href="{{ route('leaderboard.index') }}" class="nav-link">Leaderboard</a>
                <a href="{{ route('approved-candidates.index') }}" class="nav-link">Approved</a>
                <a href="{{ route('email-templates.index') }}" class="nav-link">Email Templates</a>

                <a href="{{ route('candidates.upload') }}" class="nav-upload">
                    <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 2V10M8 2L5 5M8 2L11 5" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12H14" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Upload CV
                </a>
            </div>

        </div>
    </nav>

    <!-- ── MAIN ── -->
    <main class="main">

        {{-- Success flash --}}
        @if(session('success'))
            <div class="alert alert-success">
                <svg class="alert-icon" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="7" stroke="#059669" stroke-width="1.5"/>
                    <path d="M5 8L7 10L11 6" stroke="#059669" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Error flash --}}
        @if(session('error'))
            <div class="alert alert-error">
                <svg class="alert-icon" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="7" stroke="#dc2626" stroke-width="1.5"/>
                    <path d="M8 5V8.5M8 11H8.01" stroke="#dc2626" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if($errors->any())
            <div class="alert alert-error">
                <svg class="alert-icon" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="7" stroke="#dc2626" stroke-width="1.5"/>
                    <path d="M8 5V8.5M8 11H8.01" stroke="#dc2626" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:0.2rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="content-wrapper">
            @yield('content')
        </div>

    </main>

    @include('chat.widget')

</body>
</html>
