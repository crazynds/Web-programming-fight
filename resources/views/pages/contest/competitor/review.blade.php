@extends('layouts.base')

@section('content')
    <div class="row mb-4 justify-content-between">
        <div class="col">
            <x-ballon />
            <b>
                Submission: #{{$submission->id}} ({{ $submission->user->name }})
            </b>
            <br>
            {{ $submission->created_at }}
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2>
                Code
            </h2>
            <div class="card">
                <div class="card-body">
                    <div style="margin-bottom: 4px; height: 32px;">
                        Problem: {{ $submission->problem->title }} | Result: {{ $submission->result }}
                        <a href="{{$total > $offset+1 
                                            ? route('contest.admin.competitor.review',['contest'=>$contest,'competitor'=>$competitor, 'offset'=>$offset+1])
                                            : '#'
                                }}">
                            <button style="float:right; margin-left:8px" type="button">
                                Next
                            </button>
                        </a>
                        <a href="{{$offset > 0 
                                            ? route('contest.admin.competitor.review',['contest'=>$contest,'competitor'=>$competitor, 'offset'=>$offset-1])
                                            : '#'
                                }}">
                            <button style="float:right; margin-left:8px" type="button">
                                Prev
                            </button>
                        </a>
                        <button style="float:right; margin-left:8px" type="button" class="copy" aria-label="copy" onclick="copyCode()">
                            Copy
                        </button>
                    </div>
                    <pre id="code" style="border: 1px black solid;padding: 4px">{{$submission->file->get()}}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    window.copyCode = function() {
        var range = document.createRange();
        range.selectNode(document.getElementById("code"));
        window.getSelection().removeAllRanges(); // clear current selection
        window.getSelection().addRange(range); // to select text
        document.execCommand("copy");
    }
    document.addEventListener("DOMContentLoaded", function () {
        $('#code').html(hljs.highlight(
            $('#code').text(),
            { language: '{{ 
                match ($submission->language) {
                    App\Enums\LanguagesType::name(App\Enums\LanguagesType::C) => 'c',
                    App\Enums\LanguagesType::name(App\Enums\LanguagesType::CPlusPlus) => 'cpp',
                    App\Enums\LanguagesType::name(App\Enums\LanguagesType::Java_OpenJDK24) => 'java',
                    App\Enums\LanguagesType::name(App\Enums\LanguagesType::Python3_11) => 'python',
                    App\Enums\LanguagesType::name(App\Enums\LanguagesType::Python3_13) => 'python',
                    App\Enums\LanguagesType::name(App\Enums\LanguagesType::PyPy3_10) => 'python',
                    App\Enums\LanguagesType::name(App\Enums\LanguagesType::PyPy3_11) => 'python',
                    default => 'plaintext',
                }
            }}' }
        ).value)
    });
</script>
@endsection
