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
                        {{ \Carbon\Carbon::parse($submitRun->create_at)->format('d/m/Y') }} 
                        </small>
                    </td>
                    <td class="px-2">
                        <small>
                        {{  \Carbon\Carbon::parse($submitRun->create_at)->format('h:i:s') }} 
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
                    <td class="px-2" s>
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
                    <td class="px-2">
                        <div class="hstack gap-1">
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
