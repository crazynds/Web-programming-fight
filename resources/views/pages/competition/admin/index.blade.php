@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col-8">
            <b>Brasileirao - Weekly Challenges (Admin)</b>
        </div>
        <div class="col-4">
            <a style="float:right" href="{{ route('competition.admin.create') }}">
                <button>New +</button>
            </a>
            <a style="float:right; margin-right:5px" href="{{ route('competition.admin.newsletter') }}">
                <button>Newsletter</button>
            </a>
            <a style="float:right; margin-right:5px" href="{{ route('competition.brasileirao') }}">
                <button> Leaderboard</button>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table border="1">
        <thead>
            <tr>
                <th class="px-1"><b>#</b></th>
                <th class="text-center px-2"><b>Problem</b></th>
                <th class="text-center px-2"><b>League</b></th>
                <th class="text-center px-2"><b>Ranking by</b></th>
                <th class="text-center px-2"><b>Start</b></th>
                <th class="text-center px-2"><b>End</b></th>
                <th class="text-center px-2"><b>Status</b></th>
                <th style="text-align:end" class="px-2"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @forelse($challenges as $challenge)
                <tr>
                    <td class="px-1">{{ $loop->iteration }}</td>
                    <td class="px-2">
                        <a href="{{ route('problem.show', $challenge->problem_id) }}">
                            {{ $challenge->problem->title }}
                        </a>
                    </td>
                    <td class="text-center px-2">
                        @if($challenge->league == 'bixos') Liga dos Bixos
                        @elseif($challenge->league == 'veteranos') Liga dos Veteranos
                        @else All
                        @endif
                    </td>
                    <td class="text-center px-2">{{ $challenge->rankingLabel() }}</td>
                    <td class="text-center px-2">{{ $challenge->start_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center px-2">{{ $challenge->end_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center px-2">
                        @if($challenge->isActive())
                            <span style="color:green"><b>Active</b></span>
                        @elseif($challenge->end_at->isPast())
                            <span style="color:gray">Ended</span>
                        @else
                            <span style="color:orange">Upcoming</span>
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1" style="justify-content:end">
                            <a href="{{ route('competition.admin.edit', $challenge) }}" title="Edit" class="d-flex action-btn">
                                <i class="las la-edit"></i>
                            </a>
                            <div class="vr"></div>
                            <form action="{{ route('competition.admin.destroy', $challenge) }}" method="POST">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;" title="Delete">
                                    <i class="las la-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-2 text-center text-muted">No weekly challenges yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
