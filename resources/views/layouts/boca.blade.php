<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/css/boca.css'])
    @yield('layout-head')
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
                        <font color="#000000" class="d-none d-md-inline">
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
                @if (!$contestService->inContest)
                    <td nowrap="" align="center" class="px-1 d-none d-lg-table-cell">
                        <span style="float:right; height: 20px; min-width: 100px; margin-top: -2px;">
                            <span style="float:left">
                                {{ now()->year }} |
                            </span>
                            <a class="github-button" href="https://github.com/crazynds/Web-programming-fight"
                                data-color-scheme="no-preference: light; light: light; dark: dark;"
                                data-icon="octicon-star"
                                aria-label="Star crazynds/Web-programming-fight on GitHub">Star</a>
                        </span>
                    </td>
                @endif
                <td width="99%" style="padding-left:6px;overflow: unset;">
                    @auth
                        @if (!$contestService->inContest || $contestService->started)
                            <a href="{{ route('problem.index') }}">Problems</a> |
                            {{-- <a href="{{ route('submission.create') }}">Submit</a> | --}}
                            @if (!$contestService->inContest)
                            <a href="{{ route('tag.index') }}">Tags</a> |
                            @endif
                            <a href="{{ route('submission.index') }}">Runs</a>
                        @else
                            <span style="font-size: 12pt;">
                                Wait for the contest to start...
                            </span>
                        @endif
                        {{-- <a href="./Statistics.html">Ranking</a> | --}}
                        <div class="dropdown me-2" style="float:right">
                            @if (!$contestService->inContest)
                                <a href="{{ route('forum.index') }}">Forum</a> |
                                <a href="{{ route('team.index') }}">Teams</a> |
                                <a href="{{ route('contest.index') }}">Contests</a> |
                            @else
                                <a href="{{ route('contest.competitor.leaderboard') }}">Leaderboard</a> |
                                <a href="{{ route('contest.competitor.index') }}">Competitors</a>
                                @if ($contestService->started)
                                    | <a href="{{ route('submission.global') }}">Global Runs</a>
                                @endif
                            @endif

                            @if (!$contestService->inContest)
                                <a class="dropdown-toggle" type="button" id="dropdown-headbar" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    Show More
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="dropdown-headbar">
                                    <li><a class="dropdown-item" href="{{ route('user.index') }}">Users</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="{{ route('submission.global') }}">Global Runs</a>
                                    </li>
                                    {{-- <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#">FAQ</a></li>
                                <li><a class="dropdown-item" href="#">About</a></li> --}}
                                </ul>
                            @endif
                        </div>
                    @else
                        <b style="width: calc(100% - 70px);text-align: end;display: inline-block;" class="blink">
                            Click in that button to do the login ------------------------>
                        </b>
                    @endauth
                </td>
                @if ($contestService->inContest)
                    <td nowrap align="center" class="px-2" id="headerClock">
                        <b>
                            00:00
                        </b>
                    </td>
                @endif
                <td align="center" class="px-2" nowrap="">
                    @if ($contestService->inContest)
                        <b>{{ $contestService->competitor->fullName() }}</b>
                    @else
                        @auth
                            <a class="d-none d-lg-inline" href="{{ route('user.me') }}">{{ Auth()->user()->name }}</a>


                            <a href="{{ route('user.me') }}"><img src="{{ Auth()->user()->avatar }}"
                                    class="rounded-circle" style="width: 33px;height:33px;" alt="Avatar" /></a>
                        @else
                            <span class="social-media-menu">
                                <a href="{{ route('auth.login', ['provider' => 'github']) }}" class="sm-github"></a>
                            </span>
                        @endauth
                    @endif
                </td>
                @auth
                    <td align="center" class="px-2" nowrap="">
                        @if ($contestService->inContest)
                            <a href="{{ route('contest.leave') }}" style="font-size:1.6em" title="Leave the contest!"><i
                                    class="las la-door-open"></i></a>
                        @else
                            @if (Auth::user()->isAdmin())
                                <a href="{{ route('auth.changeUser') }}">Change</a>
                                /
                            @endif
                            <a href="{{ route('auth.logout') }}">Logout</a>
                        @endif
                    </td>
                @endauth
            </tr>
        </tbody>
    </table>
    <div class="container-fluid px-5">
        <br>
        @yield('content')
    </div>
    {{-- @livewireScripts --}}
    @yield('script')
    @if ($contestService->inContest)
        <script type='module'>
            // Set the date we're counting down to
            var countDownDate = new Date(
                "{{ $contestService->started ? $contestService->contest->start_time->addMinutes($contestService->contest->duration)->toIso8601String() : $contestService->contest->start_time->toIso8601String() }}"
            ).getTime();

            const clock = function() {

                // Get today's date and time
                var now = new Date().getTime();

                // Find the distance between now and the count down date
                var distance = countDownDate - now;

                // Time calculations for days, hours, minutes and seconds
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Display the result in the element with id="clock"
                let displayTime = "";

                if (days > 0) {
                    displayTime += days.toString().padStart(2, '0') + "d ";
                    displayTime += hours.toString().padStart(2, '0') + ":";
                    displayTime += minutes.toString().padStart(2, '0') + ":";
                    displayTime += seconds.toString().padStart(2, '0');
                } else if (hours > 0) {
                    displayTime += hours.toString().padStart(2, '0') + ":";
                    displayTime += minutes.toString().padStart(2, '0') + ":";
                    displayTime += seconds.toString().padStart(2, '0');
                } else {
                    displayTime += minutes.toString().padStart(2, '0') + ":";
                    displayTime += seconds.toString().padStart(2, '0');
                }
                document.getElementById("headerClock").innerHTML = displayTime;


                // If the count down is finished, write some text
                if (distance < 0) {
                    document.getElementById("headerClock").innerHTML =
                        @if (!$contestService->started)
                            "Starting Contest..."
                        @else
                            "Ending Contest..."
                        @endif ;
                    location.reload()
                    clearInterval(x);
                } else if (minutes == 10 && seconds == 0) {
                    location.reload()
                }
            };

            clock();
            var x = setInterval(clock, 1000);
        </script>
    @endif
</body>

</html>
