<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->role, ['super_admin', 'admin', 'recruiter'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $attempts = Attempt::query()
            ->where('status', 'completed')
            ->whereHas('test', function ($query) use ($user) {
                if ($user->role !== 'super_admin') {
                    $query->where('organization_id', $user->organization_id);
                }
            })
            ->with(['test.organization', 'test.creator', 'invitation.creator'])
            ->orderByDesc('completed_at')
            ->paginate($request->integer('per_page', 15))
            ->through(function (Attempt $attempt) {
                $sharedBy = $this->resolveSharedBy($attempt);

                return [
                    'id' => $attempt->id,
                    'candidate' => $attempt->candidate_name ?: $attempt->candidate_email,
                    'candidate_email' => $attempt->candidate_email,
                    'organization_name' => $attempt->test?->organization?->name,
                    'test_title' => $attempt->test?->title,
                    'shared_by_name' => $sharedBy?->name,
                    'shared_by_email' => $sharedBy?->email,
                    'score_total' => (float) ($attempt->score_total ?? 0),
                    'score_percent' => (float) ($attempt->score_percent ?? 0),
                    'completed_at' => $attempt->completed_at,
                    'invitation_status' => $attempt->invitation?->status,
                ];
            });

        return response()->json($attempts);
    }

    public function show(Attempt $attempt)
    {
        $this->authorize('view-report', $attempt);

        return response()->json($this->buildReport($attempt));
    }

    public function exportPdf(Attempt $attempt)
    {
        $this->authorize('export-report', $attempt);

        $report = $this->buildReport($attempt);
        $pdf = Pdf::loadView('reports.attempt', [
            'attempt' => $attempt,
            'report' => $report,
        ]);

        return $pdf->download('report-'.$attempt->id.'.pdf');
    }

    public function exportCsv(Attempt $attempt)
    {
        $this->authorize('export-report', $attempt);

        $report = $this->buildReport($attempt);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report-'.$attempt->id.'.csv"',
        ];

        $callback = function () use ($report) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Section',
                'Question',
                'Type',
                'Candidate Answer',
                'Marks Awarded',
                'Max Marks',
                'Correct',
                'Organization',
                'Shared By Name',
                'Shared By Email',
            ]);

            foreach ($report['sections'] as $section) {
                foreach ($section['questions'] as $question) {
                    $candidateAnswer = $question['candidate_answer'];

                    if (is_array($candidateAnswer)) {
                        $candidateAnswer = json_encode($candidateAnswer);
                    }

                    fputcsv($handle, [
                        $section['title'],
                        $question['description'],
                        $question['type'],
                        $candidateAnswer,
                        $question['marks_awarded'],
                        $question['max_marks'],
                        $question['is_correct'] === null ? '' : ($question['is_correct'] ? 'Yes' : 'No'),
                        $report['organization_name'] ?? '',
                        $report['shared_by_name'] ?? '',
                        $report['shared_by_email'] ?? '',
                    ]);
                }
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function buildReport(Attempt $attempt): array
    {
        $attempt->loadMissing([
            'test.sections.questions.options',
            'test.organization',
            'test.creator',
            'answers.question.options',
            'invitation.creator',
        ]);

        $answersByQuestion = $attempt->answers->keyBy('question_id');
        $totalAwarded = 0.0;
        $totalMax = 0.0;

        $sections = $attempt->test->sections->map(function ($section) use ($answersByQuestion, &$totalAwarded, &$totalMax) {
            $sectionAwarded = 0.0;
            $sectionMax = 0.0;

            $questions = $section->questions->map(function ($question) use ($answersByQuestion, &$sectionAwarded, &$sectionMax, &$totalAwarded, &$totalMax) {
                $answer = $answersByQuestion->get($question->id);
                $maxMarks = (float) ($question->pivot->marks ?? $question->marks_default ?? 0);
                $awarded = $answer?->marks_awarded;

                if ($awarded === null && $question->type === 'mcq') {
                    $awarded = $answer?->is_correct ? $maxMarks : 0;
                }

                $awarded = (float) ($awarded ?? 0);
                $sectionAwarded += $awarded;
                $sectionMax += $maxMarks;
                $totalAwarded += $awarded;
                $totalMax += $maxMarks;

                return [
                    'id' => $question->id,
                    'description' => strip_tags((string) $question->description),
                    'type' => $question->type,
                    'candidate_answer' => $this->formatCandidateAnswer($question, $answer),
                    'is_correct' => $answer?->is_correct,
                    'marks_awarded' => round($awarded, 2),
                    'max_marks' => round($maxMarks, 2),
                ];
            })->values();

            return [
                'title' => $section->title,
                'score' => round($sectionAwarded, 2),
                'max_score' => round($sectionMax, 2),
                'questions' => $questions,
            ];
        })->values();

        $score = $attempt->score_total !== null ? (float) $attempt->score_total : $totalAwarded;
        $maxScore = $totalMax;
        $percentage = $attempt->score_percent !== null
            ? (float) $attempt->score_percent
            : ($maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0.0);
        $sharedBy = $this->resolveSharedBy($attempt);

        return [
            'candidate' => $attempt->candidate_name ?: $attempt->candidate_email,
            'candidate_email' => $attempt->candidate_email,
            'organization_name' => $attempt->test?->organization?->name,
            'test' => $attempt->test->title,
            'shared_by_name' => $sharedBy?->name,
            'shared_by_email' => $sharedBy?->email,
            'shared_by_role' => $sharedBy?->role,
            'invitation_status' => $attempt->invitation?->status,
            'invited_at' => $attempt->invitation?->sent_at,
            'started_at' => $attempt->started_at,
            'completed_at' => $attempt->completed_at,
            'score' => round($score, 2),
            'max_score' => round($maxScore, 2),
            'percentage' => round($percentage, 2),
            'sections' => $sections,
        ];
    }

    private function resolveSharedBy(Attempt $attempt)
    {
        return $attempt->invitation?->creator ?: $attempt->test?->creator;
    }

    private function formatCandidateAnswer($question, $answerRecord)
    {
        $answer = $answerRecord?->answer_json;

        if ($question->type === 'mcq') {
            $selectedOptionIds = collect((array) $answer)->map(fn ($id) => (string) $id);

            return $question->options
                ->whereIn('id', $selectedOptionIds)
                ->pluck('option_text')
                ->values()
                ->all();
        }

        if (is_array($answer) && (isset($answer['language']) || isset($answer['code']))) {
                return [
                    'language' => $answer['language'] ?? null,
                    'code' => $answer['code'] ?? '',
                ];
        }

        return $answer;
    }
}
