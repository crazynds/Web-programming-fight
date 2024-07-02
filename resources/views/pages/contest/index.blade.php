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
            @can('create', \App\Models\Contest::class)
                <a style="float:right" href="{{ route('contest.create') }}">
                    <button>New +</button>
                </a>
            @endcan
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th class="text-center"><b>Title</b></th>
                <th class="text-center"><b>Start Time</b></th>
                <th class="text-center"><b>Duration</b></th>
                <th class="text-center"><b>Role</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contests as $contest)
                <tr>
                    <td class="pr-2">
                        #{{ $contest->id }}
                    </td>
                    <td class="px-2">
                        {{ $contest->title }}
                    </td>
                    <td class="px-2">
                        @if ($contest->start_time->gt(now()))
                            <span style="color: green">
                                {{ $contest->start_time }}
                            </span>
                        @elseif ($contest->start_time->addMinutes($contest->duration)->gt(now()))
                            <span style="color: #bf7c00">
                                {{ $contest->start_time }}
                            </span>
                        @else
                            <span style="color: red">
                                {{ $contest->start_time }}
                            </span>
                        @endif
                    </td>
                    <td class="px-2">
                        {{ $contest->duration }} mins
                    </td>
                    <td class="px-2">
                        @if ($contest->user_id == Auth::user()->id)
                            <span title="Owner of the contest" style="cursor:help">
                                👑
                            </span>
                        @elseif (false)
                            <span title="You are a Competitor" style="cursor:help">
                                @if ($contest->individual)
                                    👤
                                @else
                                    👥
                                @endif
                            </span>
                        @elseif ($contest->is_private)
                            <span title="Private Contest" style="cursor:help">
                                🚫
                            </span>
                        @else
                            <span title="Open to public" style="cursor:help">
                                ✨
                            </span>
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @can('view', $contest)
                                @if ($contest->start_time->addMinutes($contest->duration)->gt(now()))
                                    <a href="{{ route('contest.show', ['contest' => $contest->id]) }}"
                                        title="View and enter context" class="d-flex action-btn">
                                        <i class="las la-door-open"></i>
                                    </a>
                                @else
                                    <a href="{{ route('contest.show', ['contest' => $contest->id]) }}"
                                        title="View context results" class="d-flex action-btn">
                                        <i class="las la-search"></i>
                                    </a>
                                @endif
                            @endcan
                            @can('update', $contest)
                                <div class="vr"></div>
                                <a href="{{ route('contest.edit', ['contest' => $contest->id]) }}" title="Edit this problem"
                                    class="d-flex action-btn">
                                    <i class="las la-edit"></i>
                                </a>
                            @endcan
                            @can('delete', $contest)
                                <div class="vr"></div>
                                <form action="{{ route('contest.destroy', ['contest' => $contest->id]) }}" method="POST">
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
