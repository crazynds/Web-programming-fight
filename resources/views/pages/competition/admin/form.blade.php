@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <b>{{ isset($challenge) ? 'Edit Weekly Challenge' : 'New Weekly Challenge' }}:</b>
        </div>
        <div class="col text-end">
            <a href="{{ route('competition.admin.index') }}">Go Back</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="row p-3">
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST"
          action="{{ isset($challenge) ? route('competition.admin.update', $challenge) : route('competition.admin.store') }}">
        @csrf
        @if(isset($challenge))
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-6">
                <label for="problem_id" class="form-label">Problem:</label><br />
                <select class="form-select select2" id="problem_id" name="problem_id" required>
                    <option value="">Select a problem...</option>
                    @foreach($problems as $problem)
                        <option value="{{ $problem->id }}"
                            @selected(old('problem_id', $challenge->problem_id ?? null) == $problem->id)>
                            #{{ $problem->id }} - {{ $problem->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-3">
                <label for="league" class="form-label">League:</label><br />
                <select class="form-select" id="league" name="league" required>
                    <option value="">Select league...</option>
                    <option value="bixos"     @selected(old('league', $challenge->league ?? null) == 'bixos')>Liga dos Bixos</option>
                    <option value="veteranos" @selected(old('league', $challenge->league ?? null) == 'veteranos')>Liga dos Veteranos</option>
                    <option value="all"       @selected(old('league', $challenge->league ?? null) == 'all')>All</option>
                </select>
            </div>
            <div class="col-4">
                <label for="ranking_type" class="form-label">Ranking by:</label><br />
                <select class="form-select" id="ranking_type" name="ranking_type" required>
                    <option value="">Select ranking type...</option>
                    <option value="submission_time" @selected(old('ranking_type', $challenge->ranking_type ?? null) == 'submission_time')>Submission Time (lower is better)</option>
                    <option value="cpu_time"        @selected(old('ranking_type', $challenge->ranking_type ?? null) == 'cpu_time')>CPU Time (lower is better)</option>
                    <option value="memory_peak"     @selected(old('ranking_type', $challenge->ranking_type ?? null) == 'memory_peak')>Memory Peak (lower is better)</option>
                    <option value="code_lines"      @selected(old('ranking_type', $challenge->ranking_type ?? null) == 'code_lines')>Code Lines (lower is better)</option>
                    <option value="test_cases"      @selected(old('ranking_type', $challenge->ranking_type ?? null) == 'test_cases')>Test Cases Passed (higher is better, min. 5)</option>
                </select>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-3">
                <label for="start_at" class="form-label">Start:</label><br />
                <input type="datetime-local" class="form-control" id="start_at" name="start_at"
                    value="{{ old('start_at', isset($challenge) ? $challenge->start_at->format('Y-m-d\TH:i') : '') }}"
                    required />
            </div>
            <div class="col-3">
                <label for="end_at" class="form-label">End:</label><br />
                <input type="datetime-local" class="form-control" id="end_at" name="end_at"
                    value="{{ old('end_at', isset($challenge) ? $challenge->end_at->format('Y-m-d\TH:i') : '') }}"
                    required />
            </div>
        </div>

        <p class="mt-3">
            <input type="submit" class="btn btn-primary"
                   value="{{ isset($challenge) ? 'Update' : 'Create' }}"></input>
        </p>
    </form>
@endsection
