<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @vite(['resources/css/boca.css'])
</head>
<body>
    <table border="1" width="100%">
        <tbody>
            <tr>
                <td nowrap="" bgcolor="#ffa020" align="center">
                    <x-ballon/>
                    <font color="#000000">{{ config('app.name') }}</font>
                </td>
                <td bgcolor="#ffa020" width="99%">
                    2023 |
                    <a href="{{route('problem.index')}}">Problems</a> |
                    <a href="{{route('run.create')}}">Submit</a> |
                    <a href="{{route('run.index')}}">Runs</a> |
                    <a href="./Statistics.html">Ranking</a> |
                </td>
                <td bgcolor="#ffa020" align="center" nowrap="">
                    &nbsp;contest not running&nbsp;
                </td>
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
