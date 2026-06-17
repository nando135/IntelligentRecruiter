<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Intelligent Recruiter</title>
    @vite(['resources/css/app.css'])

    <style>
        :root {
            --ink:        #0f172a;
            --ink-muted:  #64748b;
            --ink-faint:  #94a3b8;
            --surface:    #f8f9fc;
            --card:       #ffffff;
            --border:     #e4e9f0;
            --border-lt:  #f1f5f9;
            --accent:     #1d4ed8;
            --accent-lt:  #eff4ff;
            --success:    #059669;
            --danger:     #dc2626;
            --sidebar-w:  224px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: var(--surface);
            color: var(--ink);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            display: flex;
        }

        /* ═══════════════════════════════════════
           SIDEBAR
        ═══════════════════════════════════════ */
        .sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: #fff;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        /* Brand */
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0 18px;
            height: 60px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            text-decoration: none;
        }

        .sidebar-brand-icon {
            width: 28px;
            height: 28px;
            background: var(--ink);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-brand-icon svg { width: 14px; height: 14px; }

        .sidebar-brand-name {
            font-family: 'DM Serif Display', serif;
            font-size: 1rem;
            color: var(--ink);
            letter-spacing: -0.01em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Nav */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 12px 10px;
        }

        .sidebar-section-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--ink-faint);
            padding: 10px 10px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--ink-muted);
            text-decoration: none;
            transition: background .12s, color .12s;
            margin-bottom: 1px;
        }
        .sidebar-link:hover {
            background: var(--border-lt);
            color: var(--ink);
        }
        .sidebar-link.active {
            background: var(--border-lt);
            color: var(--ink);
            font-weight: 600;
        }
        .sidebar-link svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            opacity: .7;
        }
        .sidebar-link.active svg { opacity: 1; }

        /* Upload CTA */
        .sidebar-upload-wrap {
            padding: 10px 10px 0;
        }

        .sidebar-upload {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 12px;
            background: var(--ink);
            color: #fff;
            border: none;
            border-radius: 9px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s;
            letter-spacing: 0.01em;
        }
        .sidebar-upload:hover { background: #1e293b; color: #fff; }
        .sidebar-upload svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* User */
        .sidebar-user {
            padding: 12px 10px 14px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 9px;
            flex-shrink: 0;
        }

        .sidebar-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 1.5px solid var(--border);
        }

        .sidebar-avatar-fallback {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--ink);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-user-info {
            flex: 1;
            min-width: 0;
        }

        .sidebar-user-name {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-signout {
            background: none;
            border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 11.5px;
            color: var(--ink-faint);
            cursor: pointer;
            padding: 0;
            transition: color .12s;
            text-align: left;
        }
        .sidebar-signout:hover { color: var(--danger); }

        /* ═══════════════════════════════════════
           MAIN CONTENT
        ═══════════════════════════════════════ */
        .app-main {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-width: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-content {
            flex: 1;
            padding: 2.25rem 2.5rem 4rem;
            max-width: 1280px;
            width: 100%;
        }

        /* ═══════════════════════════════════════
           ALERTS
        ═══════════════════════════════════════ */
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
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-icon { flex-shrink: 0; width: 16px; height: 16px; margin-top: 1px; }

        /* ═══════════════════════════════════════
           CONTENT ANIMATION
        ═══════════════════════════════════════ */
        .content-wrapper {
            animation: fadeUp 0.25s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ═══════════════════════════════════════
           SCROLLBAR
        ═══════════════════════════════════════ */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--ink-faint); }
    </style>
</head>
<body>

    <!-- ══════════════════════════════
         SIDEBAR
    ══════════════════════════════ -->
    <aside class="sidebar">

        <!-- Brand -->
        <a href="{{ route('candidates.index') }}" class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <svg viewBox="0 0 20 20" fill="none">
                    <path d="M10 2L13.5 7H17L14 11L15.5 16L10 13L4.5 16L6 11L3 7H6.5L10 2Z" fill="white" fill-opacity="0.95"/>
                </svg>
            </div>
            <span class="sidebar-brand-name">Intelligent Recruiter</span>
        </a>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Recruitment</div>

            <a href="{{ route('candidates.index') }}"
               class="sidebar-link {{ request()->routeIs('candidates.*') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                    <circle cx="10" cy="7" r="3.5"/><path d="M3 18c0-3.87 3.13-7 7-7s7 3.13 7 7" stroke-linecap="round"/>
                </svg>
                Candidates
            </a>

            <a href="{{ route('leaderboard.index') }}"
               class="sidebar-link {{ request()->routeIs('leaderboard.*') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                    <rect x="2" y="11" width="4" height="7" rx="1"/><rect x="8" y="7" width="4" height="11" rx="1"/><rect x="14" y="3" width="4" height="15" rx="1"/>
                </svg>
                Leaderboard
            </a>

            <a href="{{ route('approved-candidates.index') }}"
               class="sidebar-link {{ request()->routeIs('approved-candidates.*') ? 'active' : '' }}">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                    <circle cx="10" cy="10" r="7.5"/><path d="M6.5 10.5L9 13L13.5 8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Approved
            </a>
        </nav>

        <!-- Upload Button -->
        <div class="sidebar-upload-wrap">
            <a href="{{ route('candidates.upload') }}" class="sidebar-upload">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 2V10M8 2L5 5M8 2L11 5"/><path d="M2 12H14"/>
                </svg>
                Upload CV
            </a>
        </div>

        <!-- User -->
        @auth
        <div class="sidebar-user">
            @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" alt="avatar" class="sidebar-avatar">
            @else
                <div class="sidebar-avatar-fallback">
                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
                </div>
            @endif
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">{{ auth()->user()->name ?? auth()->user()->email }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-signout">Sign out</button>
                </form>
            </div>
        </div>
        @endauth

    </aside>

    <!-- ══════════════════════════════
         MAIN CONTENT
    ══════════════════════════════ -->
    <div class="app-main">
        <div class="app-content">

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
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:.2rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="content-wrapper">
                @yield('content')
            </div>

        </div>
    </div>

    @include('chat.widget')

</body>
</html>
