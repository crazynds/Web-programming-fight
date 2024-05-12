@extends('layouts.boca')

@section('head')
@endsection
@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Contests:
            </b>
        </div>
        <div class="col">
            <a style="float:right" href="{{ route('contest.create') }}">
                <button>New +</button>
            </a>
        </div>
    </div>
@endsection
