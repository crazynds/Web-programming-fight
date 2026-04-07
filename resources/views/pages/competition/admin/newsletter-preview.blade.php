@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <b>Newsletter Preview</b>
        </div>
        <div class="col text-end">
            <a href="{{ route('competition.admin.newsletter') }}">Go Back</a>
        </div>
    </div>

    <p class="text-muted">
        This is how the email will look. It will be sent to <b>{{ $subscriberCount }}</b> subscribed user(s).
    </p>

    <div class="mb-3">
        <b>Subject:</b> {{ $subject }}
    </div>

    <div style="border: 1px solid #ccc; border-radius: 4px; overflow: hidden;">
        <iframe id="email-preview" style="width: 100%; height: 480px; border: 0;"></iframe>
    </div>

    <script>
        const html = @json($emailHtml);
        const iframe = document.getElementById('email-preview');
        iframe.srcdoc = html;
    </script>

    <div class="hstack gap-2 mt-3">
        <form method="POST" action="{{ route('competition.admin.newsletter.send') }}">
            @csrf
            <input type="submit" class="btn btn-primary" value="Confirm & Send">
        </form>
        <a href="{{ route('competition.admin.newsletter') }}" class="btn btn-secondary">Cancel</a>
    </div>
@endsection
