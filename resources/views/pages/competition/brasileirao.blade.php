@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col-8">
            <b>
                Brasileirao de Programacao -
                @if($league == 'geral') Overall Leaderboard
                @elseif($league == 'bixos') Liga dos Bixos
                @else Liga dos Veteranos
                @endif
            </b>
        </div>
        <div class="col-4">
            <a style="float:right; margin-left:5px" href="{{ route('competition.how-it-works') }}" title="How It Works">
                <button>?</button>
            </a>
            @can('viewPulse')
                <a style="float:right; margin-left:5px" href="{{ route('competition.admin.index') }}">
                    <button>Admin</button>
                </a>
            @endcan
            @if (!$member)
                @auth
                    <a style="float:right" href="{{ route('competition.register.show') }}">
                        <button>Register +</button>
                    </a>
                @else
                    <a style="float:right" href="{{ route('auth.login', ['provider' => 'github']) }}">
                        <button>Log in to register</button>
                    </a>
                @endauth
            @else
                <span style="float:right" class="text-muted small">
                    Registered at <strong>{{ $member->institution->acronym }}</strong>
                    as <strong>{{ ucfirst($member->role) }}</strong>
                </span>
            @endif
        </div>
    </div>

    @if (!$member)
        <div class="alert alert-warning text-center" style="font-size:1.2rem; padding: 1.5rem;">
            @auth
                <strong>You are not registered for the Brasileirao de Programacao!</strong><br>
                <a href="{{ route('competition.register.show') }}" class="btn btn-primary mt-2" style="font-size:1.1rem;">
                    Register now
                </a>
            @else
                <strong>Want to compete? Log in to register for the Brasileirao de Programacao!</strong><br>
                <a href="{{ route('auth.login', ['provider' => 'github']) }}" class="btn btn-dark mt-2" style="font-size:1.1rem;">
                    Log in to register
                </a>
            @endauth
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- League tabs --}}
    <div class="mb-3">
        {{-- <a href="{{ route('competition.brasileirao') }}"
           @if($league == 'geral') style="font-weight:bold; text-decoration:underline;" @endif>
            Overall
        </a> 
        &nbsp;|&nbsp;
        --}}
        <a href="{{ route('competition.liga.bixos') }}"
           @if($league == 'bixos') style="font-weight:bold; text-decoration:underline;" @endif>
            Liga dos Bixos
        </a>
        &nbsp;|&nbsp;
        <a href="{{ route('competition.liga.veteranos') }}"
           @if($league == 'veteranos') style="font-weight:bold; text-decoration:underline;" @endif>
            Liga dos Veteranos
        </a>
    </div>

    <div class="row">
        {{-- Leaderboard (left) --}}
        <div class="col">
            <table border="1" style="margin:unset">
                <thead>
                    <tr>
                        <th class="px-1"><b>#</b></th>
                        <th class="text-center px-2"><b>Acronym</b></th>
                        <th class="text-center px-2"><b>Members</b></th>
                        <th class="text-center px-2"><b>Solutions</b></th>
                        <th class="text-center px-2"><b>Points</b></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($institutions as $institution)
                        <tr @if($member && $member->institution_id == $institution->id && $league == $member->league) style="background: lightsteelblue;" @endif>
                            <td class="px-1">{{ $loop->iteration }}º</td>
                            <td class="px-2 text-center">
                                <a href="{{ route('competition.institution', $institution) }}"><b>{{ $institution->acronym }}</b></a>
                            </td>
                            <td class="text-center px-2">
                                @if($league == 'bixos'){{ $institution->bixos_count }}
                                @endif
                                @if($league == 'veteranos'){{ $institution->veterans_count }}
                                @endif
                            </td>
                            <td class="text-center px-2">{{ $institution->solutions }}</td>
                            <td class="text-center px-2"><b>{{ $institution->points }}</b></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 text-center text-muted">No institutions registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Weekly Challenges (right) --}}
        @if($challenges->isNotEmpty())
        <div class="col-auto">
            <b>Weekly Challenges:</b>
            <table border="1" class="mt-2">
                <thead>
                    <tr>
                        <th class="text-center px-2"><b>Problem</b></th>
                        <th class="text-center px-2"><b>Status</b></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($challenges as $challenge)
                        <tr>
                            <td class="px-2">
                                @if($challenge->start_at->isFuture())
                                    <span class="text-muted">???</span>
                                @else
                                    <a href="{{ route('competition.challenge', $challenge) }}">
                                        {{ $challenge->problem->title }}
                                    </a>
                                @endif
                            </td>
                            <td class="text-center px-2">
                                @if($challenge->isActive())
                                    <span style="color:green" class="challenge-countdown" data-end="{{ $challenge->end_at->timestamp }}"><b>Active</b></span>
                                @elseif($challenge->start_at->isFuture())
                                    <span style="color:orange">{{ $challenge->start_at->format('d/m/Y H:i') }}</span>
                                @else
                                    <span style="color:gray">{{ $challenge->end_at->format('d/m/Y H:i') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
@push('scripts')
<script>
(function () {
    function pad(n) { return String(n).padStart(2, '0'); }
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

    const els = document.querySelectorAll('.challenge-countdown');
    if (!els.length) return;

    setInterval(function () {
        els.forEach(function (el) {
            const end = parseInt(el.dataset.end) * 1000;
            const remaining = end - Date.now();
            if (remaining <= 0) {
                el.textContent = 'Ended';
                el.style.color = 'gray';
            } else {
                el.textContent = fmt(remaining);
            }
        });
    }, 1000);
})();
</script>
@endpush
@endsection
