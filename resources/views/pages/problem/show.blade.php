@extends('layouts.boca')

@section('content')

    <div style="border: #bbb solid 1px;border-radius: 3px;padding: 10px;background-color: whitesmoke;" class="shadow-lg">
        <div class="row">
            <h1 class="text-center mb-0">
                <strong>{{$problem->title}}</strong>
            </h1>
            <div class="hstack gap-2 justify-content-center">
                <div>
                    #{{ $problem->id }}
                </div>
                <div class="vr"></div>
                <small>
                    Por: <strong>{{$problem->author}}</strong>
                </small>
                <div class="vr"></div>
                <div>
                    {{$problem->memory_limit}}MB
                </div>
                <div class="vr"></div>
                <div>
                    {{ $problem->time_limit/1000 }}s
                </div>
            </div>
            <div class="hstack gap-2 justify-content-center">
            </div>
        </div>
        <hr/>
        <div class="row">
            {{Illuminate\Mail\Markdown::parse($problem->description)}}
        </div>
        <div class="row mt-2">
            <h2><strong>Input</strong></h2>
        </div>
        <div class="row">
            {{Illuminate\Mail\Markdown::parse($problem->input_description)}}
        </div>
        <div class="row mt-2">
            <h2><strong>Output</strong></h2>
        </div>
        <div class="row">
            {{Illuminate\Mail\Markdown::parse($problem->output_description)}}
        </div>

    </div>
@endsection
