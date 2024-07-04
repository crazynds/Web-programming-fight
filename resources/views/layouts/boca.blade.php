<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>{{ config('app.name') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/css/boca.css'])
    @vite(['resources/css/custom.css'])

    <style>
        .sm-github {
            background-image: url({{ asset('img/socialmediasHorizontal.webp') }});
            background-position: -66px 0;
            height: 33px;
            display: block;
        }

        .social-media-menu {
            background-repeat: no-repeat;
            display: inline-block;
            border-radius: 3px;
            border: 1px solid #bbb;
            margin: 0 0.5px;
            width: 33px;
            height: 33px;
            background-color: white;
            margin-bottom: -10px;
        }
    </style>
    @livewireStyles
    @yield('head')
</head>

<body class="no-mathjax @if ($contestService->inContest) contest @endif">
    <table border="1" width="100%">
        <tbody id="headerTab">
            <tr>
                <td nowrap="" align="center" class="px-2">
                    <a href="{{ route('home') }}"
                        style="font-size: 1em; text-decoration: none;height: 100%;display: block;width: 100%;">
                        <x-ballon />
                        <font color="#000000">
                            @if ($contestService->inContest)
                                <b>
                                    Contest Mode!
                                </b>
                            @else
                                {{ config('app.name') }}
                            @endif
                        </font>
                    </a>
                </td>
                <td width="99%" style="padding-left:6px;overflow: unset;">
                    {{ now()->year }} |
                    @if (!$contestService->inContest || $contestService->started)
                        <a href="{{ route('problem.index') }}">Problems</a> |
                        <a href="{{ route('submitRun.create') }}">Submit</a> |
                        <a href="{{ route('submitRun.index') }}">Runs</a> |
                    @else
                        <span style="font-size: 12pt;">
                            Waiting for contest to start...
                        </span>
                    @endif
                    {{-- <a href="./Statistics.html">Ranking</a> | --}}
                    <div class="dropdown me-2" style="float:right">
                        <a class="dropdown-toggle" type="button" id="dropdown-headbar" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Options
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdown-headbar">
                            @if ($contestService->inContest)
                                <li><a class="dropdown-item"
                                        href="{{ route('contest.competitor.index') }}">Competitors</a>
                                </li>
                                <li><a class="dropdown-item" href="">Leaderboard</a></li>
                            @else
                                <li><a class="dropdown-item" href="{{ route('user.index') }}">Users</a></li>
                                <li><a class="dropdown-item" href="{{ route('team.index') }}">Teams</a></li>
                                <li><a class="dropdown-item" href="{{ route('contest.index') }}">Contests</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="{{ route('submitRun.global') }}">Global Runs</a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#">FAQ</a></li>
                                <li><a class="dropdown-item" href="#">About</a></li>
                            @endif
                        </ul>
                    </div>
                </td>
                <td nowrap="" align="center" class="px-2">
                    Timer
                </td>
                <td align="center" class="px-2" nowrap="">
                    @auth
                        <a href="{{ route('user.me') }}">{{ Auth()->user()->name }}</a>
                        <img src="{{ Auth()->user()->avatar }}" class="rounded-circle" style="width: 33px;height:33px;"
                            alt="Avatar" />
                    @else
                        <span class="social-media-menu">
                            <a href="{{ route('auth.login', ['provider' => 'github']) }}" class="sm-github"></a>
                        </span>
                    @endauth
                </td>
                @auth
                    <td align="center" class="px-2" nowrap="">
                        @if ($contestService->inContest)
                            <a href="" style="font-size:1.6em" title="Leave the contest!"><i
                                    class="las la-door-open"></i></a>
                        @else
                            <a href="{{ route('auth.logout') }}">Logout</a>
                        @endif
                    </td>
                @endauth
            </tr>
        </tbody>
    </table>
    <div class="container">
        <br>
        @yield('content')
    </div>
    @livewireScripts
    @yield('script')
</body>

</html>
