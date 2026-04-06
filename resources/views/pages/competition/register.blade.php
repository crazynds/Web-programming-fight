@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <b>
                Register for Brasileirao de Programacao:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('competition.brasileirao') }}">Go Back</a>
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

        <div class="card mt-4">
        <div class="card-header">
            <h3>How It Works</h3>
        </div>
        <div class="card-body">
            <p>When you register for the Brasileirao de Programacao:</p>
            <ul>
                <li>All users from the same institution will participate as a single team</li>
                <li>You can register as one of three roles:</li>
                <ul>
                    <li><strong>Coach</strong>: Experienced mentor or instructor</li>
                    <li><strong>Bixo</strong>: Beginner with up to 1 year of programming experience</li>
                    <li><strong>Veteran</strong>: Experienced programmer with more than 1 year of programming experience</li>
                </ul>
            </ul>
            <a href="{{ route('competition.how-it-works') }}">Read more →</a>
        </div>
    </div>
    <form method="POST" action="{{ route('competition.register') }}">
        @csrf

        <div class="row">
            <div class="col-3">
                <label for="institution_acronym" class="form-label">Institution Acronym:</label><br />
                <input type="text" class="form-control" id="institution_acronym" name="institution_acronym"
                    placeholder="e.g. UFRGS" maxlength="10" required
                    value="{{ old('institution_acronym') }}" />
            </div>

        <div class="row mt-3">
            <div class="col-4">
                <label for="role" class="form-label">Category:</label><br />
                <select class="form-select" id="role" name="role" required>
                    <option value="">Select your category...</option>
                    <option value="bixo" @selected(old('role') == 'bixo')>Bixo (up to 1 year of experience)</option>
                    <option value="veteran" @selected(old('role') == 'veteran')>Veteran (more than 1 year of experience)</option>
                    <option value="coach" @selected(old('role') == 'coach')>Coach</option>
                </select>
            </div>
        </div>

        <p class="mt-3">
            <input type="submit" class="btn btn-primary" value="Register"></input>
        </p>
    </form>
@endsection
