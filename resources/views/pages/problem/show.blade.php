@extends('layouts.boca')

@section('head')
<script type="text/javascript" id="MathJax-script" async
    src="https://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML&delayStartupUntil=configured">
</script>
@endsection


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
        <div class="row mathjax">
            {{Illuminate\Mail\Markdown::parse($problem->description)}}
        </div>
        <div class="row mt-2">
            <h2><strong>Input</strong></h2>
        </div>
        <div class="row mathjax">
            {{Illuminate\Mail\Markdown::parse($problem->input_description)}}
        </div>
        <div class="row mt-2">
            <h2><strong>Output</strong></h2>
        </div>
        <div class="row mathjax">
            {{Illuminate\Mail\Markdown::parse($problem->output_description)}}
        </div>
        @if($testCases)
            <hr>
            <div class="row justify-content-center mt-2">
                <div class="col-3 text-center" style="border: solid;border-width:1px 0;margin-right: 7%;">
                    Input Example
                </div>
                <div class="col-3 text-center" style="border: solid;border-width:1px 0;">
                    Output Example
                </div>
            </div>

            @foreach ($testCases as $testCase)
                <div class="row justify-content-center mt-2">
                    <div class="col-4 px-1" style="background: #efefef;border: 1px gray solid;">
                        <pre style="margin:0">{{$testCase->inputFile->get()}}</pre>
                    </div>
                    <div class="col-4 px-1" style="background: #efefef;border: 1px gray solid;border-left:0;">
                        <pre style="margin:0">{{$testCase->outputFile->get()}}</pre>
                    </div>
                </div>
                
            @endforeach
        @endif
    </div>
@endsection

@section('script')
<script>
    window.addEventListener("load",function(){
        const func = ()=>{
            if(MathJax.typesetPromise)
                MathJax.typesetPromise()
            else{
                setTimeout(func,100)
            }
        }
        setTimeout(func,100)
    });
</script>
@endsection
