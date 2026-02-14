<x-email-layout
    title="Your Login OTP"
    heading="Your verification code"
    intro="Use this one-time password to complete your secure sign-in."
>
    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.6;color:#0f172a;">
        Hello {{ $user->name ?: 'there' }},
    </p>

    <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;color:#334155;">
        Enter the following OTP on the verification screen:
    </p>

    <p style="margin:0 0 18px 0;padding:12px 14px;border:1px dashed #94a3b8;border-radius:10px;background-color:#f8fafc;font-size:28px;font-weight:700;letter-spacing:0.25em;text-align:center;color:#0f172a;">
        {{ $otp }}
    </p>

    <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;color:#334155;">
        This OTP will expire in {{ $expiresInMinutes }} minutes.
    </p>

    <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
        Do not share this code with anyone. If you did not request this OTP, you can ignore this email.
    </p>
</x-email-layout>
