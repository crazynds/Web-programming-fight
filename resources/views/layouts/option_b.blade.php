<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Styles -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/css/modern.css', 'resources/css/option_b.css'])
    @yield('layout-head')
    @livewireStyles
    @stack('styles')
</head>

<body class="no-mathjax @if ($contestService->inContest) contest @endif">
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
                <a href="{{ route('home') }}" class="logo">
                    <x-ballon />
                    {{ config('app.name') }}
                </a>
            
            <ul class="nav-links">
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
                    
                    @if (!$contestService->inContest)
                        <li><a href="{{ route('contest.index') }}" class="{{ request()->routeIs('contest.*') && !$contestService->inContest ? 'active' : '' }}">Contests</a></li>
                        <li><a href="{{ route('forum.index') }}" class="{{ request()->routeIs('forum.*') ? 'active' : '' }}">Forum</a></li>
                    @else
                        <li><a href="{{ route('contest.competitor.leaderboard') }}" class="{{ request()->routeIs('contest.competitor.leaderboard') ? 'active' : '' }}">Leaderboard</a></li>
                        <li><a href="{{ route('contest.competitor.index') }}" class="{{ request()->routeIs('contest.competitor.*') ? 'active' : '' }}">Competitors</a></li>
                    @endif
                @endauth
            </ul>

            <div class="user-menu">
                <a class="github-button" href="https://github.com/crazynds/Web-programming-fight"
                    data-color-scheme="no-preference: light; light: light; dark: dark;"
                    data-icon="octicon-star"
                    aria-label="Star crazynds/Web-programming-fight on GitHub">Star</a>
                @auth
                    @if($contestService->inContest)
                        <span class="me-2"><b>{{ $contestService->competitor->fullName() }}</b></span>
                        <a href="{{ route('contest.leave') }}" class="btn btn-sm btn-outline-danger me-2" title="Leave the contest!">
                            <i class="fas fa-sign-out-alt"></i> Leave Contest
                        </a>
                        <div class="user-menu">
                            <img src="{{ Auth::user()->avatar }}" class="user-avatar" alt="{{ Auth::user()->name }}" id="userMenuButton">
                            
                            <div class="dropdown-menu" id="userDropdown">
                                <a href="{{ route('user.me') }}" class="dropdown-item">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <a href="{{ route('submission.global') }}" class="dropdown-item">
                                    <i class="fas fa-globe"></i> Global Runs
                                </a>
                                @if (Auth::user()->isAdmin())
                                    <div class="dropdown-divider"></div>
                                    <a href="{{ route('auth.changeUser') }}" class="dropdown-item">
                                        <i class="fas fa-exchange-alt"></i> Change User
                                    </a>
                                @endif
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('auth.logout') }}" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="user-menu">
                            <img src="{{ Auth::user()->avatar }}" class="user-avatar" alt="{{ Auth::user()->name }}" id="userMenuButton">
                            
                            <div class="dropdown-menu" id="userDropdown">
                                <a href="{{ route('user.me') }}" class="dropdown-item">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <a href="{{ route('team.index') }}" class="dropdown-item">
                                    <i class="fas fa-users"></i> Teams
                                </a>
                                <a href="{{ route('contest.index') }}" class="dropdown-item">
                                    <i class="fas fa-trophy"></i> Contests
                                </a>
                                <a href="{{ route('user.index') }}" class="dropdown-item">
                                    <i class="fas fa-users"></i> Users
                                </a>
                                <a href="{{ route('submission.global') }}" class="dropdown-item">
                                    <i class="fas fa-globe"></i> Global Runs
                                </a>
                                @if (Auth::user()->isAdmin())
                                    <div class="dropdown-divider"></div>
                                    <a href="{{ route('auth.changeUser') }}" class="dropdown-item">
                                        <i class="fas fa-exchange-alt"></i> Change User
                                    </a>
                                @endif
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('auth.logout') }}" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    @endif
                @else
                    <a href="{{ route('auth.login', ['provider' => 'github']) }}" class="login-btn">
                        <i class="fab fa-github"></i> Login with GitHub
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        @if($contestService->inContest)
            <div class="contest-banner">
                Contest Mode: {{ $contestService->contest->name }}
                @if(!$contestService->started)
                    - Contest has not started yet
                @endif
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>{{ config('app.name') }}</h3>
                <p>A OpenSource online judge platform for programming competitions.</p>
            </div>
            <div class="footer-section">
                <h3>Resources</h3>
                <ul class="footer-links">
                    <li><a target="_blank" href="https://github.com/crazynds/Web-programming-fight">Github</a></li>
                    <li><a target="_blank" href="https://blog.nextline.com.br">Blog</a></li>
                    <li><a href="{{ route('forum.index') }}">Forum</a></li>
                </ul>
            </div>
        </div>
    </footer>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userMenuButton && userDropdown) {
                // Toggle dropdown on avatar click
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target) && e.target !== userMenuButton) {
                        userDropdown.classList.remove('show');
                    }
                });

                // Close dropdown when pressing Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        userDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
