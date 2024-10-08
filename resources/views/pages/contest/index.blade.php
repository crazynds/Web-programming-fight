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
                <th class="px-1"><b>#</b></th>
                <th class="text-center"><b>Title</b></th>
                <th class="text-center px-2"><b>Problems</b></th>
                <th class="text-center px-2"><b>Competitors</b></th>
                <th class="text-center px-2"><b>Start At</b></th>
                <th class="text-center px-2"><b>Duration</b></th>
                <th class="text-center px-2"><b>Role</b></th>
                <th style="text-align: end; px-2"><b>Actions</b></th>
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
                        {{ $contest->competitors()->count() }}
                    </td>
                    <td class="px-2">
                        @if ($contest->start_time->gt(now()))
                            <span style="color: green">
                                {{ $contest->start_time->format('Y-m-d H:i') }}
                            </span>
                        @elseif ($contest->start_time->addMinutes($contest->duration)->gt(now()))
                            <span style="color: #bf7c00">
                                {{ $contest->start_time->format('Y-m-d H:i') }}
                            </span>
                        @else
                            <span style="color: red">
                                {{ $contest->start_time->format('Y-m-d H:i') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-2">
                        {{ $contest->duration }} mins
                    </td>
                    <td class="px-2">
                        @if ($contest->endTime()->lt(now()))
                            <span title="Contest ended" style="cursor:help">
                                🚫
                            </span>
                        @endif
                        @if (\Gate::allows('enter', $contest))
                            <span title="You are a Competitor" style="cursor:help">
                                @if ($contest->individual)
                                    👤
                                @else
                                    👥
                                @endif
                            </span>
                            @if ($contest->user_id == Auth::user()->id)
                                <span title="Owner of the contest" style="cursor:help">
                                    👑
                                </span>
                            @endif
                        @elseif ($contest->user_id == Auth::user()->id)
                            <span title="Owner of the contest" style="cursor:help">
                                👑
                            </span>
                        @elseif ($contest->endTime()->gt(now()))
                            @if ($contest->is_private)
                                <span title="Private Contest" style="cursor:help">
                                    🔒
                                </span>
                            @else
                                <span title="Open to public" style="cursor:help">
                                    ✨
                                </span>
                            @endif
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @can('view', $contest)
                                @if ($contest->endTime()->gt(now()))
                                    <a href="{{ route('contest.show', ['contest' => $contest->id]) }}"
                                        title="View and enter contest" class="d-flex action-btn">
                                        <i class="las la-door-open"></i>
                                    </a>
                                @else
                                    <a href="{{ route('contest.show', ['contest' => $contest->id]) }}" title="View contest"
                                        class="d-flex action-btn">
                                        <i class="las la-search"></i>
                                    </a>
                                    <a href="{{ route('problem.index') . '?contest=' . $contest->id }}"
                                        title="View contest problems" class="d-flex action-btn">
                                        <i class="las la-list-ul"></i>
                                    </a>
                                @endif
                                @if ($contest->start_time->lt(now()))
                                    <div class="vr"></div>
                                    <a href="{{ route('contest.leaderboard', ['contest' => $contest->id]) }}"
                                        title="View contest leaderboard" class="d-flex action-btn">
                                        <i class="las la-medal"></i>
                                    </a>
                                    @can('viewSubmissions', $contest)
                                        <div class="vr"></div>
                                        <a href="{{ route('contest.submissions', ['contest' => $contest->id]) }}"
                                            title="View contest submissions" class="d-flex action-btn">
                                            <i class="las la-th-list"></i>
                                        </a>
                                    @endcan
                                @endif
                            @endcan
                            @can('update', $contest)
                                <div class="vr"></div>
                                <a href="{{ route('contest.edit', ['contest' => $contest->id]) }}" title="Edit this contest"
                                    class="d-flex action-btn">
                                    <i class="las la-edit"></i>
                                </a>
                            @endcan

                            @can('admin', $contest)
                                <div class="vr"></div>
                                <a href="{{ route('contest.admin', ['contest' => $contest->id]) }}"
                                    title="Admin panel of this contest" class="d-flex action-btn">
                                    <i class="las la-chalkboard-teacher"></i>
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
