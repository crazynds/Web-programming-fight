@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Runs:
            </b>
        </div>
        <div class="col">
            <a style="float:right" href="{{ route('submission.create') }}">
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
    <x-runs-table :global="$global" :contest="$contest ?? ($contestService->contest ?? null)" />

@endsection
