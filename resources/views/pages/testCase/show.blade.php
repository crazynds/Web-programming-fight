@extends('layouts.boca')

@section('content')
    <div class="row mb-4 justify-content-between">
        <div class="col">
            <x-ballon />
            <b>
                Submit a Test Case:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{route('problem.testCase.index',['problem' => $problem->id ])}}">Go Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-6">
            <h2>
                Input
            </h2>
            <div class="card">
                <div class="card-body">
                    {{$input}}
                </div>
            </div>
        </div>
        <div class="col-6">
            <h2>
                Output
            </h2>
            <div class="card">
                <div class="card-body">
                    {{$output}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
