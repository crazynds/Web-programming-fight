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
                <th class="text-center"><b>Title</b></th>
                <th class="text-center"><b>Mem</b></th>
                <th class="text-center"><b>Time</b></th>
                <th class="text-center"><b>Accepts</b></th>
                <th class="text-center"><b>Attempts</b></th>
                <th class="text-center"><b>Writer</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($problems as $problem)
                <tr
                @if($problem->visible==false)
                class="bg-black"
                style="--bs-bg-opacity: 0.125;"
                @elseif($problem->my_accepted_submissions > 0)
                class="bg-green"
                style="--bs-bg-opacity: 0.125;"
                @endif
                >
                    <td class="pr-2">
                        #{{ $problem->id }}
                    </td>
                    <td class="px-2">
                        <a href="{{ route('problem.show', ['problem' => $problem->id]) }}">
                            {{ Str::limit($problem->title, 30) }}
                        </a>
                    </td>
                    <td class="px-2 text-center">
                        {{ $problem->memory_limit }}MB
                    </td>
                    <td class="px-2 text-center">
                        {{ $problem->time_limit / 1000 }}s
                    </td>
                    <td class="text-center">
                        @if($problem->submissions_count==0)
                            --%
                        @else
                            {{round($problem->accepted_submissions / $problem->submissions_count * 100,2)}}%
                        @endif
                    </td>
                    <td class="text-center">
                        {{$problem->submissions_count}}
                    </td>
                    <td class="text-center" class="px-2">
                        @if($problem->user)
                        <a href="{{route('user.profile',['user'=>$problem->user->id])}}">
                            {{$problem->user->name}}
                        </a>
                        @else
                        ------
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @can('view', $problem)
                                <a href="{{route('problem.show',['problem'=>$problem->id])}}" class="d-flex action-btn">
                                    <i class="las la-search"></i>
                                </a>
                                <div class="vr"></div>
                                <a href="{{route('problem.testCase.index',['problem'=>$problem->id])}}" class="d-flex action-btn">
                                    <i class="las la-folder-plus"></i>
                                </a>
                            @endcan
                            @can('update', $problem)
                                <div class="vr"></div>
                                
                                <a href="{{route('problem.edit',['problem'=>$problem->id])}}" class="d-flex action-btn">
                                    <i class="las la-edit"></i>
                                </a>

                                <div class="vr"></div>
                                <a href="{{route('problem.public',['problem'=>$problem->id])}}" class="d-flex action-btn">
                                    @if($problem->visible==false)
                                        <i class="las la-eye"></i>
                                    @else
                                        <i class="las la-eye-slash"></i>
                                    @endif
                                </a>
                            @endcan
                            @can('delete', $problem)
                                <div class="vr"></div>
                                <form action="{{route('problem.destroy',['problem'=>$problem->id])}}" method="POST">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;">
                                        <i class="las la-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
