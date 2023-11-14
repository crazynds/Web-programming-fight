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
            <a style="float:right" href="{{ route('run.create') }}">
                <button>New +</button>
            </a>
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th style="text-align: center;"><b>Date</b></th>
                <th style="text-align: center;"><b>Time</b></th>
                <th style="text-align: center;"><b>Who</b></th>
                <th style="text-align: center;"><b>Problem</b></th>
                <th style="text-align: center;"><b>Lang</b></th>
                <th style="text-align: center;"><b>Status</b></th>
                <th style="text-align: center;"><b>Result</b></th>
                <th style="text-align: center;"><b>Cases</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($submitRuns as $submitRun)
                <tr>
                    <td class="pr-2">
                        #{{ $submitRun->id }}
                    </td>
                    <td class="px-2">
                        <small>
                        {{ $submitRun->created_at->format('d/m/Y') }} 
                        </small>
                    </td>
                    <td class="px-2">
                        <small>
                        {{ $submitRun->created_at->format('h:i:s') }} 
                        </small>
                    </td>
                    <td class="px-2">
                        <small>
                            {{ $submitRun->user->name }}
                        </small>
                    </td>
                    <td class="px-2">
                        <a href="{{route('problem.show',['problem'=>$submitRun->problem->id])}}">
                            {{ $submitRun->problem->title }} 
                        </a>
                    </td>
                    <td class="px-2">
                        <small>
                            {{ $submitRun->language }} 
                        </small>
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
                        @switch($submitRun->result)
                            @case('Accepted')
                            <span style="color:#0a0">
                                All
                            </span>
                                @break
                            @case('Wrong answer')
                            <span style="color:#a00">
                                {{$submitRun->num_test_cases + 1}}
                            </span>
                                @break
                            @case('Runtime error')
                            @case('Time limit')
                            @case('Memory limit')
                            <span style="color:#00a">
                                {{$submitRun->num_test_cases + 1}}
                            </span>
                                @break
                            @case('Error')
                            @case('Compilation error')
                            @default
                                ---
                        @endswitch
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @if($submitRun->status == 'Judged' || $submitRun->status=='Error')
                                @can('update',$submitRun)
                                    <a href="{{route('run.rejudge',['submitRun'=>$submitRun->id])}}" class="d-flex" style="text-decoration:none !important;">
                                        <i class="las la-redo-alt"></i>
                                    </a>
                                @endcan
                                @can('view')
                                    @if(isset($submitRun->output))
                                            <a href="{{route('run.show',['submitRun'=>$submitRun->id])}}" class="d-flex" style="text-decoration:none !important;">
                                                <i class="las la-poll-h"></i>
                                            </a>
                                    @endif
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
