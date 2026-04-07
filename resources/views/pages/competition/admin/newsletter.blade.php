@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <b>Send Newsletter - Brasileirão de Programação</b>
        </div>
        <div class="col text-end">
            <a href="{{ route('competition.admin.index') }}">Go Back</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="text-muted">Will be sent to <b>{{ $subscriberCount }}</b> subscribed user(s).</p>

    <form method="POST" action="{{ route('competition.admin.newsletter.preview') }}">
        @csrf
        <div class="row">
            <div class="col-8">
                <label for="subject" class="form-label">Subject:</label>
                <input type="text" class="form-control" id="subject" name="subject"
                       value="{{ old('subject') }}" required maxlength="255" />
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-8">
                <label for="body" class="form-label">Message:</label>
                <textarea class="form-control" id="body" name="body" rows="12" required>{{ old('body') }}</textarea>
            </div>
        </div>
        <p class="mt-3">
            <input type="submit" class="btn btn-primary" value="Send Newsletter">
        </p>
    </form>
@endsection
