<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .footer { margin-top: 40px; font-size: 0.85em; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <h2>Brasileirão de Programação</h2>

    <p>Olá, {{ $user->name }}!</p>

    <div>{!! nl2br(e($body)) !!}</div>

    <div class="footer">
        <p>You are receiving this email because you are registered on the platform.</p>
        <p>
            To stop receiving all emails from this platform,
            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('competition.unsubscribe', now()->addDays(30), ['user' => $user->id]) }}">
                click here to unsubscribe
            </a>.
        </p>
    </div>
</body>
</html>
