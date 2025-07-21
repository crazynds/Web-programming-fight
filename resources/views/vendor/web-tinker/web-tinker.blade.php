<!doctype html>
<html lang="en">
<head>
    <title>Tinker</title>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" href="{{ asset('vendor/web-tinker/img/favicon.png') }}"/>
    <link rel="stylesheet" crossorigin href="{{ asset('vendor/web-tinker/app.css') }}"/>
    @if(app()->environment('production'))
        <script src="{{ asset('vendor/web-tinker/production.js') }}" crossorigin type="module"></script>
    @else
        <script src="{{ asset('vendor/web-tinker/development.js') }}" crossorigin type="module"></script>
    @endif
</head>
<body>
<div id="root" data-path="/tinker"></div>
</body>
</html>
