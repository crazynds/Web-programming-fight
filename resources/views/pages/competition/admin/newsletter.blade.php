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

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

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
                <label class="form-label">Audience:</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="audience" id="aud_all" value="all"
                               {{ old('audience', 'all') === 'all' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="aud_all">Everyone</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="audience" id="aud_registered" value="registered"
                               {{ old('audience') === 'registered' ? 'checked' : '' }}>
                        <label class="form-check-label" for="aud_registered">Registered only</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="audience" id="aud_unregistered" value="unregistered"
                               {{ old('audience') === 'unregistered' ? 'checked' : '' }}>
                        <label class="form-check-label" for="aud_unregistered">Not registered only</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-8">
                <label for="body" class="form-label">Message:</label>
                <textarea class="form-control" id="body" name="body" rows="12" required>{{ old('body') }}</textarea>
            </div>
        </div>
        <p class="mt-3">
            <input type="submit" class="btn btn-primary" value="Preview Newsletter">
        </p>
    </form>

    @if ($newsletters->isNotEmpty())
        <hr class="my-4">
        <h6>Previous Newsletters</h6>
        <table border="1" class="mt-2">
            <thead>
                <tr>
                    <th class="px-2">#</th>
                    <th class="px-2">Subject</th>
                    <th class="px-2">Audience</th>
                    <th class="px-2 text-center">Total</th>
                    <th class="px-2 text-center">Sent</th>
                    <th class="px-2 text-center">Failed</th>
                    <th class="px-2 text-center">Pending</th>
                    <th class="px-2">Date</th>
                    <th class="px-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($newsletters as $nl)
                    <tr>
                        <td class="px-2">{{ $nl->id }}</td>
                        <td class="px-2">{{ $nl->subject }}</td>
                        <td class="px-2">{{ ucfirst($nl->audience) }}</td>
                        <td class="px-2 text-center">{{ $nl->total }}</td>
                        <td class="px-2 text-center text-success">{{ $nl->sent_count }}</td>
                        <td class="px-2 text-center {{ $nl->failed_count > 0 ? 'text-danger' : '' }}">{{ $nl->failed_count }}</td>
                        <td class="px-2 text-center text-muted">{{ $nl->pending_count }}</td>
                        <td class="px-2">{{ $nl->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-2">
                            <a href="{{ route('competition.admin.newsletter.show', $nl) }}">Details</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
