@extends('layouts.boca')

@section('head')
    <script>
        window.MathJax = {
            processClass: "mathjax",
            ignoreClass: "no-mathjax",
            tex: {
                inlineMath: [
                    ['$', '$']
                ]
            }
        }
    </script>
    <script id="MathJax-script" async src="{{ asset('js/mathjax/tex-chtml.js') }}"></script>
@endsection


@section('content')

    <div style="border: #bbb solid 1px;border-radius: 3px;padding: 10px;background-color: whitesmoke; margin: 0 15%"
        class="shadow-lg">
        <div class="row">
            <h1 class="text-center mb-0">
                <strong>{{ $problem->title }}</strong>
            </h1>
            <div class="hstack gap-2 justify-content-center">
                <div>
                    #{{ $problem->id }}
                </div>
                <div class="vr"></div>
                <small>
                    Made by: <strong>{{ $problem->author }}</strong>
                </small>
                <div class="vr"></div>
                <div>
                    {{ $problem->memory_limit }}MB
                </div>
                <div class="vr"></div>
                <div>
                    {{ $problem->time_limit / 1000 }}s
                </div>
                <div class="vr"></div>
                <a href="{{ route('submitRun.create', ['problem' => $problem->id]) }}">
                    Submit
                </a>

            </div>
            <div class="hstack gap-2 justify-content-center">
            </div>
        </div>
        <hr />
        <div class="row mathjax">
            {{ Illuminate\Mail\Markdown::parse($problem->description) }}
        </div>
        <div class="row mt-2">
            <h2><strong>Input</strong></h2>
        </div>
        <div class="row mathjax">
            {{ Illuminate\Mail\Markdown::parse($problem->input_description) }}
        </div>
        <div class="row mt-2">
            <h2><strong>Output</strong></h2>
        </div>
        <div class="row mathjax">
            {{ Illuminate\Mail\Markdown::parse($problem->output_description) }}
        </div>
        @if (sizeof($testCases) > 0)
            <hr>
            <div class="row justify-content-center my-2">
                <div class="col-3 text-center" style="border: solid;border-width:1px 0;margin-right: 10%;">
                    Input Example
                </div>
                <div class="col-3 text-center" style="border: solid;border-width:1px 0;">
                    Output Example
                </div>
            </div>

            @foreach ($testCases as $testCase)
                <div class="row justify-content-center py-2">
                    <div class="col-4 px-1 mx-2"
                        style="background: #efefef;border: 1px gray solid; min-height: 60px; position: relative">
                        <button style="position: absolute; top: 5px; right: 5px"
                            onclick="copyCode('input{{ $testCase->id }}')">Copy</button>
                        <pre style="margin:0" id="input{{ $testCase->id }}">{{ $testCase->inputFile->get() }}</pre>
                    </div>
                    <div class="col-4 px-1 mx-2"
                        style="background: #efefef;border: 1px gray solid; min-height: 60px; position: relative">
                        <button style="position: absolute; top: 5px; right: 5px"
                            onclick="copyCode('output{{ $testCase->id }}')">Copy</button>
                        <pre style="margin:0" id="output{{ $testCase->id }}">{{ $testCase->outputFile->get() }}</pre>
                    </div>
                </div>
                <hr style="max-width: 70%; margin: auto">
            @endforeach
        @endif
    </div>
@endsection

@section('script')
    <script>
        function copyCode(id) {
            var range = document.createRange();
            range.selectNode(document.getElementById(id));
            window.getSelection().removeAllRanges(); // clear current selection
            window.getSelection().addRange(range); // to select text
            document.execCommand("copy");
        }
    </script>
@endsection
