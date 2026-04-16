@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col-8">
            <b>Newsletter #{{ $newsletter->id }} — {{ $newsletter->subject }}</b>
        </div>
        <div class="col-4 text-end">
            <a href="{{ route('competition.admin.newsletter') }}">Go Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row mb-3">
        <div class="col-auto">
            <span class="text-muted">Total:</span> <b>{{ $newsletter->total }}</b>
        </div>
        <div class="col-auto text-success">
            <span class="text-muted">Sent:</span> <b>{{ $sends->where('status', 'sent')->count() }}</b>
        </div>
        <div class="col-auto text-danger">
            <span class="text-muted">Failed:</span> <b>{{ $sends->where('status', 'failed')->count() }}</b>
        </div>
        <div class="col-auto text-muted">
            <span class="text-muted">Pending:</span> <b>{{ $sends->where('status', 'pending')->count() }}</b>
        </div>
        @if ($sends->where('status', 'failed')->count() > 0)
            <div class="col-auto">
                <form method="POST" action="{{ route('competition.admin.newsletter.resend', $newsletter) }}">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">Resend to failed</button>
                </form>
            </div>
        @endif
    </div>

    <table border="1">
        <thead>
            <tr>
                <th class="px-2">User</th>
                <th class="px-2">Email</th>
                <th class="px-2 text-center">Status</th>
                <th class="px-2">Sent at</th>
                <th class="px-2">Error</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sends as $send)
                <tr>
                    <td class="px-2">{{ $send->user->name }}</td>
                    <td class="px-2">{{ $send->user->email }}</td>
                    <td class="px-2 text-center
                        @if($send->status === 'sent') text-success
                        @elseif($send->status === 'failed') text-danger
                        @else text-muted @endif">
                        {{ ucfirst($send->status) }}
                    </td>
                    <td class="px-2">{{ $send->sent_at ?? '—' }}</td>
                    <td class="px-2 text-danger small">{{ $send->error ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-3">
        {{ $sends->links() }}
    </div>
@endsection
