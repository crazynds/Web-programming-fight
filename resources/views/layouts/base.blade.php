@extends('layouts.option_b')

@section('layout-head')
<title>{{ config('app.name') }}</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
@vite(['resources/css/custom.css'])
<script async defer src="https://buttons.github.io/buttons.js"></script>
<script>
    window.env = {
        REVERB_APP_KEY: '{{ config('reverb.apps.apps.0.key') }}',
        REVERB_HOST: '{{ config('reverb.apps.apps.0.options.host') }}',
        REVERB_SCHEME: '{{ config('reverb.apps.apps.0.options.scheme') }}',
        REVERB_PORT: '{{ config('reverb.apps.apps.0.options.port') }}',
        REVERB_PATH: '',
        LIVEWIRE: {{ config('app.livewire') ? 'true' : 'false' }},
    }
</script>

<style>
.sm-github {
    background-image: url({{ asset('img/socialmediasHorizontal.webp') }});
    background-position: -66px 0;
    height: 33px;
    display: block;
};
</style>

@endsection