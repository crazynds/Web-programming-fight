<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f0f4f8; font-family:Arial,sans-serif; color:#2c3e50;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8; padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.10);">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#ffffff; padding:20px 32px; border-bottom:3px solid #155986;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <span style="font-size:20px; font-weight:700; color:#155986; letter-spacing:0.3px;">
                                            Programming Fight
                                        </span>
                                    </td>
                                    <td align="right">
                                        <span style="font-size:12px; color:#aaa;">{{ config('app.url') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero banner --}}
                    <tr>
                        <td style="background:linear-gradient(135deg, #155986 0%, #3498db 100%); padding:40px 32px; text-align:center;">
                            <div style="font-size:40px; margin-bottom:12px;">🏆</div>
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:0.4px;">
                                Brasileirão de Programação
                            </h1>
                            <p style="margin:10px 0 0; color:#d6eaf8; font-size:14px;">
                                Competição nacional de programação competitiva
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 32px 28px;">
                            <p style="margin:0 0 24px; font-size:16px; color:#2c3e50;">
                                Olá, <strong>{{ $user->name }}</strong>!
                            </p>
                            <div style="font-size:15px; line-height:1.85; color:#555555; white-space:pre-line; border-left:3px solid #3498db; padding-left:16px;">{{ $body }}</div>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:36px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.url') }}/brasileirao"
                                           style="display:inline-block; background-color:#155986; color:#ffffff; text-decoration:none; padding:14px 40px; border-radius:6px; font-size:15px; font-weight:700; letter-spacing:0.3px;">
                                            Acessar o Brasileirão &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px 28px; background-color:#f8f9fa; border-top:1px solid #e8ecf0; text-align:center;">
                            <p style="margin:0 0 6px; font-size:12px; color:#aaa;">
                                You are receiving this email because you are registered on the platform.
                            </p>
                            <p style="margin:0; font-size:12px; color:#aaa;">
                                To stop receiving emails,
                                <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('competition.unsubscribe', now()->addDays(30), ['user' => $user->id]) }}"
                                   style="color:#3498db; text-decoration:none;">unsubscribe here</a>.
                            </p>
                        </td>
                    </tr>

                </table>

                <p style="margin-top:20px; font-size:11px; color:#bbb; text-align:center;">
                    &copy; {{ date('Y') }} Programming Fight. All rights reserved.
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
