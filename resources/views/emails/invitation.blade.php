@php
    $candidateName = $invitation->candidate_name ?: 'Candidate';
    $organizationName = optional(optional($invitation->test)->organization)->name ?: config('app.name');
    $testTitle = optional($invitation->test)->title ?: 'Assessment';
@endphp

<x-email-layout
    title="Assessment Invitation"
    heading="You're invited to an assessment"
    :intro="$organizationName.' has invited you to take: '.$testTitle"
    :button-url="$url"
    button-text="Start Assessment"
    button-hint="If the button does not open, copy the link shown below into your browser."
>
    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.6;color:#0f172a;">
        Hello {{ $candidateName }},
    </p>

    <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;color:#334155;">
        You can start your test immediately by clicking <strong>Start Assessment</strong>. The timer will begin only after you click <strong>Start Test</strong> on the instructions page.
    </p>

    <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;color:#334155;">
        Link expires on: <strong>{{ optional($invitation->expires_at)->toDayDateTimeString() }}</strong>
    </p>

    <p style="margin:0 0 8px 0;font-size:13px;line-height:1.6;color:#64748b;">
        Direct link:
    </p>
    <p style="margin:0;padding:10px 12px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;word-break:break-all;font-size:12px;line-height:1.6;color:#334155;">
        {{ $url }}
    </p>
</x-email-layout>
