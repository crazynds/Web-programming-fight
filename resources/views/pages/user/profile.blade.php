@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Profile:
            </b>
        </div>
    </div>

    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-md-8 col-xl-4">

            <div class="card" style="border-radius: 15px;">
                <div class="card-body text-center">
                    <div class="mt-3 mb-4">
                        <img src="{{ $user->avatar }}" class="rounded-circle img-fluid" style="width: 100px;" />
                    </div>
                    <h4 class="mb-2">
                        {{ $user->name }}
                    </h4>
                    </a>


                    <p class="text-muted mb-4">
                        @if ($user->url != null)
                            <a href="{{ $user->url }}" target="_blank">
                                @Github</a>
                            <span class="mx-2">|</span>
                        @endif

                        <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                    </p>
                    <div class="d-flex justify-content-between text-center mt-5 mb-2">
                        <div>
                            <p class="mb-2 h5">{{ $problems_count }}</p>
                            <p class="text-muted mb-0">Problems Solved</p>
                        </div>
                        <div class="px-3">
                            <p class="mb-2 h5">{{ $accepted_count }}</p>
                            <p class="text-muted mb-0">Accepts</p>
                        </div>
                        <div>
                            <p class="mb-2 h5">{{ $attempts_count }}</p>
                            <p class="text-muted mb-0">Submissions</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>



    <table border="1" style="margin: auto;margin-top: 10px;">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th class="text-center"><b>When</b></th>
                <th class="text-center"><b>Problem</b></th>
                <th class="text-center"><b>Lang</b></th>
                <th class="text-center"><b>Result</b></th>
                <th class="text-center"><b>Resources</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($submissions as $submitRun)
                <tr data-id="{{ $submitRun->id }}" @if ($submitRun->status != 'Judged' && $submitRun->status != 'Error') class="notJudged blink" @endif>
                    <td>
                        @can('view', $submitRun)
                            <a href="#" onclick="openModal({{ $submitRun->id }})">
                                #{{ $submitRun->id }}
                            </a>
                        @else
                            #{{ $submitRun->id }}
                        @endcan
                    </td>
                    <td class="px-1 text-center">
                        <small>
                            @if ($submitRun->created_at->format('d/m/Y') != (new DateTime())->format('d/m/Y'))
                                {{ $submitRun->created_at->format('d/m/Y') }}
                            @else
                                {{ $submitRun->created_at->format('H:i:s') }}
                            @endif
                        </small>
                    </td>
                    <td class="px-1">
                        <a href="{{ route('problem.show', ['problem' => $submitRun->problem->id]) }}">
                            {{ $submitRun->problem->title }}
                        </a>
                    </td>
                    <td class="px-1">
                        {{ $submitRun->language }}
                    </td>
                    <td class="px-2">
                        <span id="result"
                            @switch($submitRun->result)
                            @case('Accepted')
                                style="color:#0a0"
                                @break
                            @case('Error')
                                style="color:#f00"
                                @break
                            @case('Wrong answer')
                                style="color:#a00"
                                @break
                            @case('Compilation error')
                            @case('Runtime error')
                                style="color:#aa0"
                                @break
                            @case('Time limit')
                            @case('Memory limit')
                                style="color:#00a"
                                @break
                            @default
                                style="color:grey"
                        @endswitch>
                            {{ $submitRun->result }}
                        </span>
                    </td>
                    <td class="px-2" style="font-size: 0.9em">
                        @if (isset($submitRun->execution_time) && $submitRun->status == 'Judged')
                            {{ number_format($submitRun->execution_time / 1000, 2, '.', ',') }}s
                        @else
                            --
                        @endif
                        |
                        @if (isset($submitRun->execution_memory) && $submitRun->status == 'Judged')
                            {{ $submitRun->execution_memory }} MB
                        @else
                            --
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>



    <div class="modal fade codeModal" id="codeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document" style="max-width:80%">
            <div class="modal-content" style="padding: 10px;padding-top:4px;">
                <div style="margin-bottom: 4px">
                    <button style="float:right" type="button" class="copy" aria-label="copy" onclick="copyCode()">
                        Copy
                    </button>
                </div>
                <pre id="code" style="border: 1px black solid">Código na linguagem</pre>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const copyCode = function() {
            var range = document.createRange();
            range.selectNode(document.getElementById("code"));
            window.getSelection().removeAllRanges(); // clear current selection
            window.getSelection().addRange(range); // to select text
            document.execCommand("copy");
        }
        var openModal = function() {}
        window.addEventListener("load", function() {
            openModal = function(id) {
                var url = '{{ route('api.submitRun.code', ['submitRun' => -1]) }}'.replace('-1', id)
                $('#codeModal').modal("show")
                $('#codeModal').find('#code').html(`
                <div class="d-flex justify-content-center">
                    <div class="spinner-grow" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `)
                $.get(url, function(data) {
                    if (data.code)
                        $('#codeModal').find('#code').text(data.code)
                });
            }
        })
    </script>
@endsection
