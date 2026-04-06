@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col-8">
            <b>
                Brasileirao de Programacao - Weekly Challenge
                @if($challenge->league !== 'all')
                    ({{ $challenge->league === 'bixos' ? 'Liga dos Bixos' : 'Liga dos Veteranos' }})
                @endif
            </b>
        </div>
        <div class="col-4">
            @can('viewPulse')
                <a style="float:right; margin-left:5px" href="{{ route('competition.admin.index') }}">
                    <button>Admin</button>
                </a>
            @endcan
            <a style="float:right" href="{{ route('competition.liga.bixos') }}">
                <button> Leaderboard</button>
            </a>
        </div>
    </div>

    {{-- Problem info card --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <b>
                    <a href="{{ route('problem.show', $challenge->problem_id) }}">
                        {{ $challenge->problem->title }}
                    </a>
                </b>
                @if($challenge->problem->author)
                    &mdash; {{ $challenge->problem->author }}
                @endif
            </span>
            <span id="countdown" style="font-size:1.1em; font-weight:bold;"></span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-auto">
                    <small class="text-muted">Time Limit</small><br>
                    <b>{{ $challenge->problem->time_limit / 1000 }}s</b>
                </div>
                <div class="col-auto">
                    <small class="text-muted">Memory Limit</small><br>
                    <b>{{ $challenge->problem->memory_limit }} MB</b>
                </div>
                <div class="col-auto">
                    <small class="text-muted">Start</small><br>
                    <b>{{ $challenge->start_at->format('d/m/Y H:i') }}</b>
                </div>
                <div class="col-auto">
                    <small class="text-muted">End</small><br>
                    <b>{{ $challenge->end_at->format('d/m/Y H:i') }}</b>
                </div>
                <div class="col-auto">
                    <small class="text-muted">Ranking by</small><br>
                    <b>{{ $challenge->rankingLabel() }}</b>
                </div>
            </div>
        </div>
    </div>

    {{-- Leaderboard --}}
    <b>Leaderboard:</b>
    <table border="1" class="mt-2">
        <thead>
            <tr>
                <th class="px-1"><b>#</b></th>
                <th class="text-center px-2"><b>User</b></th>
                <th class="text-center px-2"><b>Institution</b></th>
                @if($challenge->ranking_type === 'cpu_time')
                    <th class="text-center px-2"><b>CPU Time (ms)</b></th>
                @elseif($challenge->ranking_type === 'memory_peak')
                    <th class="text-center px-2"><b>Memory Peak (KB)</b></th>
                @elseif($challenge->ranking_type === 'code_lines')
                    <th class="text-center px-2"><b>Code Lines</b></th>
                @else
                    <th class="text-center px-2"><b>Submission Time</b></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($leaderboard as $i => $submission)
                @php($isMe = $submission->user_id === Auth::id())
                <tr @if($isMe) style="background: lightsteelblue;" @endif>
                    <td class="px-1">
                        {{ $i + 1 }}º
                        @if($bonusSubmissionIds->contains($submission->id))
                            🏆
                        @endif
                    </td>
                    <td class="px-2">
                        <a href="{{ route('user.profile', $submission->user_id) }}">
                            {{ $submission->user->name }}
                        </a>
                    </td>
                    <td class="text-center px-2">
                        @php($inst = \App\Models\BrasileiraoMember::where('user_id', $submission->user_id)->with('institution')->first())
                        {{ $inst?->institution?->acronym ?? '-' }}
                    </td>
                    @if($challenge->ranking_type === 'cpu_time')
                        <td class="text-center px-2">{{ $submission->execution_time ?? '-' }}</td>
                    @elseif($challenge->ranking_type === 'memory_peak')
                        <td class="text-center px-2">{{ $submission->execution_memory ?? '-' }}</td>
                    @elseif($challenge->ranking_type === 'code_lines')
                        <td class="text-center px-2">{{ $submission->code_lines ?? '-' }}</td>
                    @else
                        <td class="text-center px-2">
                            +{{ $submission->created_at->diffInSeconds($challenge->start_at) }}s
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-2 text-center text-muted">No accepted submissions yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('script')
<script>
    const endTime = new Date("{{ $challenge->end_at->toIso8601String() }}").getTime();
    const startTime = new Date("{{ $challenge->start_at->toIso8601String() }}").getTime();
    const el = document.getElementById('countdown');

    function tick() {
        const now = Date.now();

        if (now < startTime) {
            const diff = startTime - now;
            el.textContent = 'Starts in ' + fmt(diff);
            el.style.color = 'orange';
        } else if (now <= endTime) {
            const diff = endTime - now;
            el.textContent = 'Ends in ' + fmt(diff);
            el.style.color = diff < 600000 ? 'red' : 'green';
        } else {
            el.textContent = 'Challenge ended';
            el.style.color = 'gray';
        }
    }

    function fmt(ms) {
        const s = Math.floor(ms / 1000);
        const d = Math.floor(s / 86400);
        const h = Math.floor((s % 86400) / 3600);
        const m = Math.floor((s % 3600) / 60);
        const sec = s % 60;
        if (d > 0) return d + 'd ' + pad(h) + ':' + pad(m) + ':' + pad(sec);
        if (h > 0) return pad(h) + ':' + pad(m) + ':' + pad(sec);
        return pad(m) + ':' + pad(sec);
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    tick();
    setInterval(tick, 1000);
</script>
@endsection
