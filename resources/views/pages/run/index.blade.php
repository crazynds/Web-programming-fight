@extends('layouts.boca')

@section('head')
    <style>
        .blink {
            -webkit-animation: blink 2s infinite both;
            animation: blink 2s infinite both;
        }

        @-webkit-keyframes blink {

            0%,
            50%,
            100% {
                opacity: 1;
            }

            25%,
            75% {
                opacity: 0.4;
            }
        }

        @keyframes blink {

            0%,
            50%,
            100% {
                opacity: 1;
            }

            25%,
            75% {
                opacity: 0.4;
            }
        }
    </style>
@endsection
@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Runs:
            </b>
        </div>
        <div class="col">
            <a style="float:right" href="{{ route('submitRun.create') }}">
                <button>New Submission +</button>
            </a>
        </div>
    </div>
    @if ($errors->any())
        <div class="row p-3">
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if(config('app.livewire'))
        <livewire:runs-table-component :global="$global" :contest="$contest ?? ($contestService->contest ?? null)" />
    @else
        <x-runs-table :global="$global" :contest="$contest ?? ($contestService->contest ?? null)" />
    @endif

@endsection
