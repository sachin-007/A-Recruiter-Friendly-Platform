<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Assessment Invitation</title>
</head>
<body>
    <h2>Hello{{ $invitation->candidate_name ? ' '.$invitation->candidate_name : '' }},</h2>
    <p>You have been invited to take an assessment.</p>
    <p>Instructions: open the link below and click <strong>Start Test</strong> to begin your timer.</p>
    <p>
        <a href="{{ $url }}">Start Assessment</a>
    </p>
    <p>This link will expire on {{ optional($invitation->expires_at)->toDayDateTimeString() }}.</p>
</body>
</html>
