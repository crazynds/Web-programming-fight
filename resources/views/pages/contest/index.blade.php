@extends('layouts.boca')

@section('head')
@endsection
@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Contests:
            </b>
        </div>
        <div class="col">
            <a style="float:right" href="{{ route('contest.create') }}">
                <button>New +</button>
            </a>
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th class="text-center"><b>Title</b></th>
                <th class="text-center"><b>Problems</b></th>
                <th class="text-center"><b>Subscribers</b></th>
                <th class="text-center"><b>Start At</b></th>
                <th class="text-center"><b>Duration</b></th>
                <th class="text-center"><b>Status</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contests as $contest)
                <tr>
                    <td class="pr-2">
                        #{{ $contest->id }}
                    </td>
                    <td class="px-2 text-center">
                        {{ $contest->title }}
                    </td>
                    <td class="px-2 text-center">
                        {{ $contest->problems()->count() }}
                    </td>
                    <td class="px-2 text-center">
                        0
                    </td>
                    <td class="px-2 text-center">
                        {{ $contest->start_time->format('Y-m-d H:i') }}
                    </td>
                    <td class="px-2 text-center">
                        {{ $contest->duration }} mins
                    </td>
                    <td class="px-2 text-center">
                        <span
                            @switch($contest->status())
                            @case('Open')
                                style="color:#0a0"
                                @break
                            @case('Closed ')
                                style="color:#a00"
                                @break
                            @case('In Progress')
                                style="color:#aa0"
                                @break
                            @default
                                style="color:grey"
                        @endswitch>
                            {{ $contest->status() }}
                        </span>
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @can('view', $contest)
                                <a href="{{ route('contest.show', ['contest' => $contest->id]) }}" title="View contest"
                                    class="d-flex action-btn">
                                    <i class="las la-search"></i>
                                </a>
                            @endcan
                            @can('update', $contest)
                                <div class="vr"></div>
                                <a href="{{ route('contest.edit', ['contest' => $contest->id]) }}" title="Edit this contest"
                                    class="d-flex action-btn">
                                    <i class="las la-edit"></i>
                                </a>
                            @endcan
                            @can('delete', $contest)
                                <div class="vr"></div>
                                <form action="{{ route('contest.destroy', ['contest' => $contest->id]) }}" method="POST">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;"
                                        title="Delete this contest">
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
