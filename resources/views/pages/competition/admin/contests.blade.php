@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col-8">
            <b>Brasileirao - Contests Vinculados (Admin)</b>
        </div>
        <div class="col-4">
            <a style="float:right" href="{{ route('competition.admin.index') }}">
                <button>← Voltar</button>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Vincular novo contest --}}
    <div class="mb-4">
        <b>Vincular contest ao Brasileirão</b>
        <form action="{{ route('competition.admin.contests.attach') }}" method="POST" class="mt-2 d-flex gap-2 flex-wrap align-items-end">
            @csrf
            <div>
                <label>Contest</label>
                <select name="contest_id" required class="form-select form-select-sm">
                    <option value="">Selecione...</option>
                    @foreach ($allContests as $contest)
                        <option value="{{ $contest->id }}">
                            [{{ $contest->id }}] {{ $contest->title }}
                            ({{ $contest->start_time->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Liga</label>
                <select name="league" required class="form-select form-select-sm">
                    <option value="all">Ambas as ligas</option>
                    <option value="bixos">Liga dos Bixos</option>
                    <option value="veteranos">Liga dos Veteranos</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-sm">Vincular</button>
            </div>
        </form>
    </div>

    {{-- Lista de contests vinculados --}}
    <table border="1">
        <thead>
            <tr>
                <th class="px-2"><b>#</b></th>
                <th class="px-2"><b>Contest</b></th>
                <th class="text-center px-2"><b>Liga</b></th>
                <th class="text-center px-2"><b>Início</b></th>
                <th class="text-center px-2"><b>Status</b></th>
                <th style="text-align:end" class="px-2"><b>Ações</b></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($linkedContests as $bc)
                <tr>
                    <td class="px-1">{{ $loop->iteration }}</td>
                    <td class="px-2">
                        <a href="{{ route('contest.show', $bc->contest) }}">
                            {{ $bc->contest->title }}
                        </a>
                    </td>
                    <td class="text-center px-2">{{ $bc->leagueLabel() }}</td>
                    <td class="text-center px-2">{{ $bc->contest->start_time->format('d/m/Y H:i') }}</td>
                    <td class="text-center px-2">
                        @if ($bc->scored_at)
                            <span style="color:gray">Pontuado em {{ $bc->scored_at->format('d/m/Y H:i') }}</span>
                        @elseif ($bc->contest->endTime()->isPast())
                            <span style="color:orange">Encerrado (não pontuado)</span>
                        @else
                            <span style="color:green">Em andamento</span>
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1" style="justify-content:end">
                            <form action="{{ route('competition.admin.contests.score', $bc) }}" method="POST"
                                  onsubmit="return confirm('Isso vai (re)calcular a pontuação deste contest. Confirma?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-secondary" title="Pontuar">
                                    Pontuar
                                </button>
                            </form>
                            <div class="vr"></div>
                            <form action="{{ route('competition.admin.contests.detach', $bc) }}" method="POST"
                                  onsubmit="return confirm('Deseja desvincular este contest do Brasileirão? A pontuação gerada por ele NÃO será revertida automaticamente.')">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;" title="Desvincular">
                                    <i class="las la-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-2 text-center text-muted">Nenhum contest vinculado ainda.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
