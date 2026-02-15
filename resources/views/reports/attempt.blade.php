<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Candidate Report - {{ $report['candidate'] }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; }
        .header { margin-bottom: 20px; }
        .title { font-size: 20px; margin-bottom: 6px; }
        .meta { color: #4b5563; margin-bottom: 2px; }
        .score { font-size: 16px; font-weight: 700; margin-top: 8px; }
        .section-title { margin: 18px 0 8px; font-size: 14px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f3f4f6; }
        pre { white-space: pre-wrap; margin: 0; font-family: Consolas, monospace; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $report['test'] }} - Candidate Report</div>
        <div class="meta">Candidate: {{ $report['candidate'] }} ({{ $report['candidate_email'] }})</div>
        <div class="meta">Organization: {{ $report['organization_name'] ?: '-' }}</div>
        <div class="meta">
            Shared By:
            {{ $report['shared_by_name'] ?: 'N/A' }}
            @if(!empty($report['shared_by_email']))
                ({{ $report['shared_by_email'] }})
            @endif
        </div>
        <div class="meta">Invited: {{ optional($report['invited_at'])->format('Y-m-d H:i') ?: '-' }}</div>
        <div class="meta">Completed: {{ optional($report['completed_at'])->format('Y-m-d H:i') }}</div>
        <div class="score">
            Score: {{ $report['score'] }} / {{ $report['max_score'] }} ({{ $report['percentage'] }}%)
        </div>
    </div>

    @foreach($report['sections'] as $section)
        <div class="section-title">
            {{ $section['title'] }} - {{ $section['score'] }} / {{ $section['max_score'] }}
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 36%">Question</th>
                    <th style="width: 10%">Type</th>
                    <th style="width: 36%">Candidate Answer</th>
                    <th style="width: 8%">Correct</th>
                    <th style="width: 10%">Marks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section['questions'] as $question)
                    @php($answer = $question['candidate_answer'])
                    <tr>
                        <td>{{ $question['description'] }}</td>
                        <td>{{ $question['type'] }}</td>
                        <td>
                            @if(is_array($answer) && isset($answer['code']))
                                <div><strong>Language:</strong> {{ $answer['language'] ?? '-' }}</div>
                                <pre>{{ $answer['code'] }}</pre>
                            @elseif(is_array($answer))
                                {{ json_encode($answer) }}
                            @else
                                {{ $answer ?: 'No answer' }}
                            @endif
                        </td>
                        <td>
                            @if($question['is_correct'] === null)
                                -
                            @else
                                {{ $question['is_correct'] ? 'Yes' : 'No' }}
                            @endif
                        </td>
                        <td>{{ $question['marks_awarded'] }} / {{ $question['max_marks'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
