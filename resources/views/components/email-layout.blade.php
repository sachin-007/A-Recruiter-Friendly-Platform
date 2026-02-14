@props([
    'title' => config('app.name'),
    'heading' => config('app.name'),
    'intro' => null,
    'buttonUrl' => null,
    'buttonText' => null,
    'buttonHint' => null,
    'footerText' => 'This is an automated email from '.config('app.name').'.',
])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f3f6fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:640px;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0f172a,#1e293b);padding:22px 28px;border-radius:14px 14px 0 0;">
                            <p style="margin:0 0 8px 0;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#cbd5e1;">{{ config('app.name') }}</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.35;color:#ffffff;">{{ $heading }}</h1>
                            @if ($intro)
                                <p style="margin:12px 0 0 0;font-size:14px;line-height:1.55;color:#e2e8f0;">{{ $intro }}</p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#ffffff;padding:26px 28px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
                            {{ $slot }}

                            @if ($buttonUrl && $buttonText)
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;">
                                    <tr>
                                        <td align="center" style="border-radius:8px;background-color:#0f172a;">
                                            <a href="{{ $buttonUrl }}" style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                                                {{ $buttonText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if ($buttonHint)
                                <p style="margin:14px 0 0 0;font-size:12px;line-height:1.5;color:#64748b;">{{ $buttonHint }}</p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f8fafc;padding:16px 28px;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 14px 14px;">
                            <p style="margin:0;font-size:12px;line-height:1.5;color:#64748b;">{{ $footerText }}</p>
                            <p style="margin:8px 0 0 0;font-size:12px;color:#94a3b8;">{{ now()->year }} {{ config('app.name') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
