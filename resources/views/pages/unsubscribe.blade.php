@extends('layouts.base')

@section('content')
    <div class="row mt-5 justify-content-center">
        <div class="col-6 text-center">
            <h4>Unsubscribed</h4>
            <p>You have been removed from the platform email list and will no longer receive any emails.</p>
            <a href="{{ route('home') }}">Back to Home</a>
        </div>
    </div>
@endsection
