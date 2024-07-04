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
            @if (!$contestService->inContest)
                <a style="float:right" href="{{ route('problem.create') }}">
                    <button>New +</button>
                </a>
                @if (Auth::user()->isAdmin())
                    <a style="float:right; margin-right: 5px;" href="{{ route('problem.import') }}">
                        <button>Import +</button>
                    </a>
                @endif
            @endif
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th class="px-1"><b>#</b></th>
                <th class="text-center px-2"><b>Title</b></th>
                <th class="text-center px-2"><b>Mem</b></th>
                <th class="text-center px-2"><b>Time</b></th>
                <th class="text-center px-2"><b>Accepts</b></th>
                <th class="text-center px-2"><b>Attempts</b></th>
                @if (!$contestService->inContest)
                    <th class="text-center px-2"><b>Writer</b></th>
                @endif
                <th style="text-align: end;" class="px-2"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @php($number = 'A')
            @foreach ($problems as $problem)
                <tr
                    @if ($problem->visible == false) class="bg-black"
                style="--bs-bg-opacity: 0.125;"
                @elseif($problem->my_accepted_submissions > 0)
                class="bg-success"
                style="--bs-bg-opacity: 0.125;" @endif>
                    <td class="pr-2">
                        @if ($contestService->inContest)
                            <b class="px-2" style="font-size: 1.6em">
                                {{ $number++ }}
                        </b @else #{{ $problem->id }} @endif
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
                        @if ($problem->submissions_count == 0)
                            --%
                        @else
                            {{ round(($problem->accepted_submissions / $problem->submissions_count) * 100, 2) }}%
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $problem->submissions_count }}
                    </td>
                    @if (!$contestService->inContest)
                        <td class="text-center" class="px-2">
                            @if ($problem->user)
                                <a href="{{ route('user.profile', ['user' => $problem->user->id]) }}">
                                    {{ $problem->user->name }}
                                </a>
                            @else
                                ------
                            @endif
                        </td>
                    @endif
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @if ($contestService->inContest)
                                <a href="{{ route('contest.problem.show', ['problem' => $problem->id]) }}"
                                    title="View problem" class="d-flex action-btn">
                                    <i class="las la-search"></i>
                                </a>
                            @else
                                @can('view', $problem)
                                    <a href="{{ route('problem.show', ['problem' => $problem->id]) }}" title="View problem"
                                        class="d-flex action-btn">
                                        <i class="las la-search"></i>
                                    </a>
                                    @if ($problem->ranks_count > 0)
                                        <div class="vr"></div>
                                        <a href="{{ route('problem.podium', ['problem' => $problem->id]) }}" title="Ranking"
                                            class="d-flex action-btn">
                                            <i class="las la-trophy"></i>
                                        </a>
                                    @endif
                                    <div class="vr"></div>
                                    <a href="{{ route('problem.testCase.index', ['problem' => $problem->id]) }}"
                                        title="Test cases" class="d-flex action-btn">
                                        <i class="las la-vial"></i>
                                    </a>
                                @endcan
                                @can('update', $problem)
                                    <div class="vr"></div>
                                    <a href="{{ route('problem.scorer.index', ['problem' => $problem->id]) }}"
                                        title="Edit scorers" class="d-flex action-btn">
                                        <i class="las la-star"></i>
                                    </a>

                                    <div class="vr"></div>
                                    <a href="{{ route('problem.download', ['problem' => $problem->id]) }}"
                                        title="Download this problem" target="_blank" class="d-flex action-btn">
                                        <i class="las la-file-archive"></i>
                                    </a>

                                    <div class="vr"></div>
                                    <a href="{{ route('problem.edit', ['problem' => $problem->id]) }}"
                                        title="Edit this problem" class="d-flex action-btn">
                                        <i class="las la-edit"></i>
                                    </a>

                                    <div class="vr"></div>
                                    <a href="{{ route('problem.public', ['problem' => $problem->id]) }}"
                                        title="Enable/Disable problem" class="d-flex action-btn">
                                        @if ($problem->visible == false)
                                            <i class="las la-eye"></i>
                                        @else
                                            <i class="las la-eye-slash"></i>
                                        @endif
                                    </a>
                                @endcan
                                @can('delete', $problem)
                                    <div class="vr"></div>
                                    <form action="{{ route('problem.destroy', ['problem' => $problem->id]) }}" method="POST">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;"
                                            title="Delete this problem">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
