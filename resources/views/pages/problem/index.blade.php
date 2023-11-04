@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Problems:
            </b>
        </div>
        <div class="col">
            <a style="float:right" href="{{ route('problem.create') }}">
                <button>New +</button>
            </a>
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th style="text-align: center;"><b>Title</b></th>
                <th style="text-align: center;"><b>Mem</b></th>
                <th style="text-align: center;"><b>Time</b></th>
                <th style="text-align: center;"><b>Accepts</b></th>
                <th style="text-align: center;"><b>Attempts</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($problems as $problem)
                <tr>
                    <td class="pr-2">
                        #{{ $problem->id }}
                    </td>
                    <td class="px-2">
                        <a href="{{ route('problem.show', ['problem' => $problem->id]) }}">
                            {{ Str::limit($problem->title, 30) }}
                        </a>
                    </td>
                    <td class="px-2" style="text-align: center;">
                        {{ $problem->memory_limit }}MB
                    </td>
                    <td class="px-2" style="text-align: center;">
                        {{ $problem->time_limit / 1000 }}s
                    </td>
                    <td style="text-align: center;">
                        0%
                    </td>
                    <td style="text-align: center;">
                        0
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            <a href="{{route('problem.show',['problem'=>$problem->id])}}" class="d-flex" style="text-decoration:none !important;">
                                <i class="las la-eye"></i>
                            </a>
                            <div class="vr"></div>
                            
                            <a href="{{route('problem.edit',['problem'=>$problem->id])}}" class="d-flex" style="text-decoration:none !important;">
                                <i class="las la-edit"></i>
                            </a>
                            <div class="vr"></div>
                            <a href="{{route('problem.testCase.index',['problem'=>$problem->id])}}" class="d-flex" style="text-decoration:none !important;">
                                <i class="las la-folder-plus"></i>
                            </a>
                            <div class="vr"></div>
                            <form action="{{route('problem.destroy',['problem'=>$problem->id])}}" method="POST">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;">
                                    <i class="las la-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
