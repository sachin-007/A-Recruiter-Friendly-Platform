<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Candidate Report - {{ $attempt->candidate_name ?? $attempt->candidate_email }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .score { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $attempt->test->title }} - Report</h2>
        <p>Candidate: {{ $attempt->candidate_name ?? $attempt->candidate_email }}</p>
        <p>Completed: {{ $attempt->completed_at->format('Y-m-d H:i') }}</p>
        <p class="score">Score: {{ $attempt->score_total }} / {{ $attempt->answers->sum(fn($a) => $a->question->pivot?->marks ?? $a->question->marks_default) }} ({{ $attempt->score_percent }}%)</p>
    </div>

    @foreach($attempt->test->sections as $section)
        <h3>{{ $section->title }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Type</th>
                    <th>Answer</th>
                    <th>Marks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section->questions as $question)
                    @php
                        $answer = $attempt->answers->firstWhere('question_id', $question->id);
                    @endphp
                    <tr>
                        <td>{{ strip_tags($question->description) }}</td>
                        <td>{{ $question->type }}</td>
                        <td>
                            @if($question->type === 'mcq')
                                @php
                                    $selected = is_array($answer?->answer_json) ? $answer->answer_json : [];
                                    $correctOptions = $question->options->where('is_correct', true)->pluck('id')->toArray();
                                    $isCorrect = !array_diff($selected, $correctOptions) && !array_diff($correctOptions, $selected);
                                @endphp
                                {{ $isCorrect ? 'Correct' : 'Incorrect' }}
                            @else
                                {{ $answer?->answer_json ?? 'No answer' }}
                            @endif
                        </td>
                        <td>{{ $answer->marks_awarded ?? ($answer->is_correct ? ($question->pivot->marks ?? $question->marks_default) : 0) }} / {{ $question->pivot->marks ?? $question->marks_default }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>