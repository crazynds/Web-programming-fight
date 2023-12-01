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
            <a style="float:right" href="{{ route('submitRun.create') }}">
                <button>New +</button>
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

    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th class="text-center"><b>When</b></th>
                <th class="text-center"><b>Who</b></th>
                <th class="text-center"><b>Problem</b></th>
                <th class="text-center"><b>Lang</b></th>
                <th class="text-center"><b>Status</b></th>
                <th class="text-center"><b>Result</b></th>
                <th class="text-center"><b>Cases</b></th>
                <th class="text-center"><b>Resources</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($submitRuns as $submitRun)
                <tr>
                    <td>
                        @can('view',$submitRun)
                        <a href="#" onclick="openModal({{$submitRun->id}})">
                            #{{ $submitRun->id }}
                        </a>
                        @else
                            #{{ $submitRun->id }}
                        @endcan
                    </td>
                    <td class="px-1 text-center">
                        <small>
                        @if($submitRun->created_at->format('d/m/Y') != (new DateTime())->format('d/m/Y'))
                            {{ $submitRun->created_at->format('d/m/Y') }}
                        @else
                            {{ $submitRun->created_at->format('H:i:s') }}
                        @endif
                        </small>
                    </td>
                    <td class="px-1">
                            {{ $submitRun->user->name }}
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
                        <strong>
                            {{ $submitRun->status }}
                        </strong>
                    </td>
                    <td class="px-2">
                        <span
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
                    <td class="px-2 text-center">
                        <small>
                        @switch($submitRun->result)
                        @case('Accepted')
                            <span style="color:#0a0">
                                All
                            </span>
                        @break

                        @case('Wrong answer')
                            <span style="color:#a00">
                                {{ $submitRun->num_test_cases + 1 }}
                            </span>
                        @break

                        @case('Runtime error')
                        @case('Time limit')

                        @case('Memory limit')
                            <span style="color:#00a">
                                {{ $submitRun->num_test_cases + 1 }}
                            </span>
                        @break

                        @case('Error')
                        @case('Compilation error')

                            @default
                                ---
                        @endswitch
                        </small>
                    </td>
                    <td class="px-2" style="font-size: 0.9em">
                        @if(isset($submitRun->execution_time))
                        {{ number_format($submitRun->execution_time/1000, 2, '.', ',') }}s
                        @else
                        --
                        @endif
                        |
                        @if(isset($submitRun->execution_memory))
                        {{ $submitRun->execution_memory}} MB
                        @else
                        --
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @if ($submitRun->status == 'Judged' || $submitRun->status == 'Error')
                                @can('update',$submitRun)
                                    @if(\Illuminate\Support\Facades\RateLimiter::remaining('resubmission:'.Auth::user()->id, 5))
                                        <a href="{{ route('submitRun.rejudge', ['submitRun' => $submitRun->id]) }}"
                                            class="d-flex action-btn">
                                            <i class="las la-redo-alt"></i>
                                        </a>
                                    @endif
                                @endcan
                                @can('view',$submitRun)
                                    @if (isset($submitRun->output))
                                    <a href="{{ route('submitRun.show', ['submitRun' => $submitRun->id]) }}"
                                        class="d-flex action-btn">
                                        <i class="las la-poll-h"></i>
                                    </a>
                                    @endif
                                @endcan
                            @endif
                            @can('view',$submitRun)
                                @if ($submitRun->file()->exists())
                                    <a target="_blank" href="{{ route('submitRun.download', ['submitRun' => $submitRun->id]) }}"
                                        class="d-flex action-btn">
                                        <i class="las la-file-download"></i>
                                    </a>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="modal fade codeModal" id="codeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document" style="max-width:80%">
            <div class="modal-content" style="padding: 10px;">
                <pre id="code" style="border: 1px black solid">Código na linguagem</pre>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        var openModal = function(){}
        window.addEventListener("load",function(){
            var timeout = null

            timeout = setTimeout(function(){
                window.location.reload(1);
            }, 6000);
            $('.codeModal').on('hide.bs.modal', function () {
                timeout = setTimeout(function(){
                    window.location.reload(1);
                }, 6000);
            })
            openModal = function(id){
                var url = '{{route('api.submitRun.code',['submitRun'=>-1])}}'.replace('-1',id)
                $('.codeModal').modal("show")
                clearTimeout(timeout);
                $('.codeModal').find('#code').html(`
                    <div class="d-flex justify-content-center">
                        <div class="spinner-grow" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                `)
                $.get(url,function(data){
                    if(data.code)
                        $('.codeModal').find('#code').text(data.code) 
                });
            }
        })
    </script>
@endsection
