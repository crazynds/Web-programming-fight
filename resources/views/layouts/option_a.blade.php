<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/css/modern.css'])
    @yield('layout-head')
    <style>
        :root {
            --header-height: 3.5rem;
            --sidebar-width: 15rem;
            --topbar-bg: #f0f0f0;
            --topbar-border: #b9b9b9;
            --link-color: #1a5a96;
            --link-hover: #1a5a96;
            --header-bg: #f5f5f5;
            --header-text: #333;
            --header-hover: #e8e8e8;
            --content-bg: #fff;
            --sidebar-bg: #f5f5f5;
            --sidebar-hover: #e8e8e8;
            --border-color: #ddd;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--content-bg);
            color: #333;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding: 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }


        .content-container{
            padding: 1rem 2rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
        }


        .header {
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--topbar-border);
            padding: 0.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: var(--header-height);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--header-text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo img {
            height: 2rem;
        }

        .top-menu {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .top-menu a {
            color: var(--link-color);
            text-decoration: none;
            font-size: 0.95rem;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }

        .top-menu a:hover {
            background-color: var(--header-hover);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
        }

        .contest-banner {
            background-color: #ffeb3b;
            padding: 0.5rem 1rem;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #ffc107;
        }

        .nav-section {
            margin: 1.5rem 0;
        }

        .nav-section h3 {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            margin: 0 0 0.5rem 0;
            padding: 0 0.5rem;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links li {
            margin: 0.25rem 0;
        }

        .nav-links a {
            display: block;
            padding: 0.4rem 0.75rem;
            color: #333;
            text-decoration: none;
            border-radius: 3px;
            font-size: 0.95rem;
        }

        .nav-links a:hover {
            background-color: var(--sidebar-hover);
        }

        .nav-links .active {
            background-color: #e3f2fd;
            color: #1976d2;
            font-weight: 500;
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
    @livewireStyles
    @yield('head')
</head>

<body class="no-mathjax @if ($contestService->inContest) contest @endif">
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <x-ballon />
                {{ config('app.name') }}
            </div>

            <div class="nav-section">
                <h3>Menu</h3>
                <ul class="nav-links">
                    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
                    @auth
                        @if (!$contestService->inContest || $contestService->started)
                            <li><a href="{{ route('problem.index') }}" class="{{ request()->routeIs('problem.*') ? 'active' : '' }}">Problems</a></li>
                            @if (!$contestService->inContest)
                                <li><a href="{{ route('tag.index') }}" class="{{ request()->routeIs('tag.*') ? 'active' : '' }}">Tags</a></li>
                            @endif
                            <li><a href="{{ route('submission.index') }}" class="{{ request()->routeIs('submission.index') ? 'active' : '' }}">Runs</a></li>
                            @if ($contestService->inContest && $contestService->started)
                                <li><a href="{{ route('submission.global') }}" class="{{ request()->routeIs('submission.global') ? 'active' : '' }}">Global Runs</a></li>
                            @endif
                        @endif
                    @endauth
                </ul>
            </div>

            @auth
                @if (!$contestService->inContest)
                    <div class="nav-section">
                        <h3>Community</h3>
                        <ul class="nav-links">
                            <li><a href="{{ route('forum.index') }}" class="{{ request()->routeIs('forum.*') ? 'active' : '' }}">Forum</a></li>
                            <li><a href="{{ route('team.index') }}" class="{{ request()->routeIs('team.*') ? 'active' : '' }}">Teams</a></li>
                            <li><a href="{{ route('user.index') }}" class="{{ request()->routeIs('user.index') ? 'active' : '' }}">Users</a></li>
                        </ul>
                    </div>

                    <div class="nav-section">
                        <h3>Contests</h3>
                        <ul class="nav-links">
                            <li><a href="{{ route('contest.index') }}" class="{{ request()->routeIs('contest.*') && !$contestService->inContest ? 'active' : '' }}">Contest List</a></li>
                        </ul>
                    </div>
                @else
                    <div class="nav-section">
                        <h3>Contest</h3>
                        <ul class="nav-links">
                            <li><a href="{{ route('contest.competitor.leaderboard') }}" class="{{ request()->routeIs('contest.competitor.leaderboard') ? 'active' : '' }}">Leaderboard</a></li>
                            <li><a href="{{ route('contest.competitor.index') }}" class="{{ request()->routeIs('contest.competitor.*') ? 'active' : '' }}">Competitors</a></li>
                            @if ($contestService->started)
                                <li><a href="{{ route('submission.global') }}" class="{{ request()->routeIs('submission.global') ? 'active' : '' }}">Global Runs</a></li>
                            @endif
                            <li><a href="{{ route('contest.leave') }}" class="text-danger">Leave Contest</a></li>
                        </ul>
                    </div>
                @endif
            @endauth
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="top-menu">
                    @if($contestService->inContest)
                        <span class="contest-banner">Contest Mode: {{ $contestService->contest->name }}</span>
                    @endif
                </div>
                
                <div class="user-menu">
                    @auth
                        @if($contestService->inContest)
                            <span><b>{{ $contestService->competitor->fullName() }}</b></span>
                        @else
                            <a href="{{ route('user.me') }}" class="d-none d-lg-inline">{{ Auth::user()->name }}</a>
                            <a href="{{ route('user.me') }}">
                                <img src="{{ Auth::user()->avatar }}" class="user-avatar" alt="Avatar" />
                            </a>
                            @if (Auth::user()->isAdmin())
                                <a href="{{ route('auth.changeUser') }}">Change User</a>
                            @endif
                            <a href="{{ route('auth.logout') }}">Logout</a>
                        @endif
                    @else
                        <a href="{{ route('auth.login', ['provider' => 'github']) }}" class="btn btn-outline-dark">
                            <i class="fab fa-github"></i> Login with GitHub
                        </a>
                    @endauth
                </div>
            </header>

            <!-- Page Content -->
            <main class="content-container mt-3">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
