@extends('layouts.boca')

@section('content')
    <div class="row mb-4 justify-content-between">
        <div class="col">
            <x-ballon />
            <b>
                Submission: #{{$submitRun->id}}
            </b>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2>
                Output
            </h2>
            <div class="card">
                <div class="card-body">
                    <pre>{{$output}}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
