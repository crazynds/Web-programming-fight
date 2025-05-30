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
            },
            skipHtmlTags: [ //  HTML tags that won't be searched for math
                'script', 'noscript', 'style', 'textarea',
                'annotation', 'annotation-xml'
            ],
        }
    </script>
    <style>
        .content img {
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script id="MathJax-script" async src="{{ asset('js/mathjax/tex-chtml.js') }}"></script>
@endsection


@section('content')
    <div class="row justify-content-center">
        <div style="border: #bbb solid 1px;border-radius: 3px;padding: 10px;background-color: whitesmoke;"
            class="shadow-lg col-9">
            <div class="row">
                <h1 class="text-center mb-0">
                    <strong>{{ $problem->title }}</strong>
                </h1>
                <div class="hstack gap-2 justify-content-center">
                    <div>
                        #{{ $problem->id }}
                    </div>
                    @if (!$contestService->inContest)
                        <div class="vr"></div>
                        <input type="hidden" class="star-rating rating" data-show-clear="false"
                            data-problem-id="{{ $problem->id }}" data-show-caption="false" data-size="sm"
                            @if (!$accepted) value="{{ $problem->rating / 2.0 }}" data-readonly="true" 
                        @else
                        value="{{ (\App\Models\Rating::where('problem_id', $problem->id)->where('user_id', Auth::id())->first()?->value ?? $problem->rating) / 2.0 }}" @endif>
                    @endif
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
            <div class="row mathjax content">
                <div clas="col">

                    {{ Illuminate\Mail\Markdown::parse($problem->description) }}
                </div>
            </div>
            <div class="row mt-2">
                <h2><strong>Input</strong></h2>
            </div>
            <div class="row mathjax content">
                <div clas="col">

                    {{ Illuminate\Mail\Markdown::parse($problem->input_description) }}
                </div>
            </div>
            <div class="row mt-2">
                <h2><strong>Output</strong></h2>
            </div>
            <div class="row mathjax content">
                <div clas="col">

                    {{ Illuminate\Mail\Markdown::parse($problem->output_description) }}
                </div>
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
                    <div class="row justify-content-center pt-2">
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
                    @if ($testCase->explanation)
                        <div class="row justify-content-center py-2">
                            <p class="col-9" style="font-size: 0.75em">
                                <b>
                                    Explanation {{ $loop->index + 1 }}:
                                </b>
                                <br>
                                <i class="mathjax"
                                    style="white-space: pre-wrap; font-family: monospace; word-wrap:break-word;">{{ $testCase->explanation }}</i>
                            </p>
                        </div>
                    @endif
                    <hr style="max-width: 70%; margin-left: auto; margin-right: auto;" class="mt-1">
                @endforeach
            @endif
        </div>
        @if ($contestService->inContest)
            <div class="col-3" style="margin-right: -80px;">
                <h5>
                    Clarifications
                </h5>
                @foreach ($clarifications as $clarification)
                    <div style="border: #bbb solid 1px;border-radius: 3px;padding: 10px;background-color: whitesmoke;margin-right: 15%;font-size: 0.8em;"
                        class="shadow-md mb-1 mr-2">
                        <b>
                            @if ($contestService->competitor->id == $clarification->competitor_id)
                                Your
                            @endif
                            Question
                        </b><br>
                        {{ $clarification->question }}
                    </div>
                    <div style="border: #bbb solid 1px;border-radius: 3px;padding: 10px;background-color: whitesmoke; margin-left: 15%;font-size: 0.8em;"
                        class="shadow-md mb-3 mathjax">
                        @if (isset($clarification->answer))
                            <b style="width: 100%;text-align: right;display: inline-block;">
                                Answer
                                @if ($clarification->public)
                                    (Public)
                                @endif
                            </b><br>
                            {{ Illuminate\Mail\Markdown::parse($clarification->answer) }}
                        @else
                            <span style="color: gray">
                                Not answered yet...
                            </span>
                        @endif
                    </div>
                @endforeach
                <form action="{{ route('contest.clarification.store') }}" method="POST" style="margin-bottom: 42px;">
                    @csrf
                    <input type="hidden" name="problem_id" value="{{ $problem->id }}" />

                    <label for="question">
                        Question:
                    </label>
                    <textarea class="form-control" id="question" name="question" rows="3"></textarea>
                    <button type="submit" style="float:right">Ask</button>
                </form>
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
            </div>
        @endif
    </div>
@endsection

@section('script')
    <script type='module'>
        window.copyCode = function(id) {
            var range = document.createRange();
            range.selectNode(document.getElementById(id));
            window.getSelection().removeAllRanges(); // clear current selection
            window.getSelection().addRange(range); // to select text
            document.execCommand("copy");
        }
        $(document).ready(function() {

            MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

            var trackChange = function(element) {
                var observer = new MutationObserver(function(mutations, observer) {
                    if (mutations[0].attributeName == "value") {
                        $(element).trigger("change");
                    }
                });
                observer.observe(element, {
                    attributes: true
                });
            }

            // Hidden input does not trigger change events, so this is needed to trigger it.
            $(".star-rating").each((idx, el) => trackChange(el));

            $('.star-rating').change(function() {
                $.ajax({
                    type: "POST",
                    url: "{{ route('problem.rating.store', ['problem' => -1]) }}".replace('-1',
                        $(this).data('problem-id')),
                    data: {
                        value: parseInt(this.value * 2)
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            })
        })
    </script>
@endsection
