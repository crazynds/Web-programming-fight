<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/css/boca.css'])
    <style>
        .sm-github {
            background-image: url({{asset("img/socialmediasHorizontal.webp")}});
            background-position: -66px 0;
            height: 33px;
            display: block;
        }
        .social-media-menu{
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
</head>
<body>
    <table border="1" width="100%">
        <tbody>
            <tr>
                <td nowrap="" bgcolor="#ffa020" align="center">
                    <x-ballon/>
                    <font color="#000000">{{ config('app.name') }}</font>
                </td>
                <td bgcolor="#ffa020" width="99%" style="padding-left:6px;">
                    2023 |
                    <a href="{{route('problem.index')}}">Problems</a> |
                    <a href="{{route('run.create')}}">Submit</a> |
                    <a href="{{route('run.index')}}">Runs</a> |
                    <a href="{{route('run.global')}}">Global</a> |
                    {{-- <a href="./Statistics.html">Ranking</a> | --}}
                </td>
                <td bgcolor="#ffa020" align="center" class="px-2" nowrap="">
                    @auth
                        <a href="{{route('user.me')}}">{{Auth()->user()->name}}</a>
                        <img src="{{Auth()->user()->avatar}}" class="rounded-circle" style="width: 33px;height:33px;"
                            alt="Avatar" />
                    @else
                    <span class="social-media-menu">
                        <a href="{{route('auth.login',['provider' => 'github'])}}" class="sm-github"></a>
                    </span>
                    @endauth
                </td>
                @auth
                <td bgcolor="#ffa020" align="center" class="px-2" nowrap="">
                    <a href="{{route('auth.logout')}}">Logout</a>
                </td>
                @endauth
            </tr>
        </tbody>
    </table>
    <div class="container">
        <br>
        @yield('content')
    </div>

    @yield('script')
</body>
</html>
