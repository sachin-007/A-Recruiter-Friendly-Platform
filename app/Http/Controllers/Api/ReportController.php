<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function show(Attempt $attempt)
    {
        $this->authorize('view-report', $attempt);

        $attempt->load([
            'test.sections.questions.options',
            'answers.question',
            'invitation'
        ]);

        // Prepare report data
        $report = [
            'candidate' => $attempt->candidate_name ?? $attempt->candidate_email,
            'test' => $attempt->test->title,
            'started_at' => $attempt->started_at,
            'completed_at' => $attempt->completed_at,
            'score' => $attempt->score_total,
            'max_score' => $attempt->answers->sum(fn($a) => $a->question->pivot?->marks ?? $a->question->marks_default),
            'percentage' => $attempt->score_percent,
            'sections' => $attempt->test->sections->map(function ($section) use ($attempt) {
                $sectionQuestions = $section->questions;
                $sectionAnswers = $attempt->answers->whereIn('question_id', $sectionQuestions->pluck('id'));

                return [
                    'title' => $section->title,
                    'questions' => $sectionQuestions->map(function ($q) use ($sectionAnswers) {
                        $answer = $sectionAnswers->firstWhere('question_id', $q->id);
                        return [
                            'description' => $q->description,
                            'type' => $q->type,
                            'candidate_answer' => $answer?->answer_json,
                            'is_correct' => $answer?->is_correct,
                            'marks_awarded' => $answer?->marks_awarded ?? ($answer?->is_correct ? ($q->pivot->marks ?? $q->marks_default) : 0),
                            'max_marks' => $q->pivot->marks ?? $q->marks_default,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json($report);
    }

    public function exportPdf(Attempt $attempt)
    {
        $this->authorize('export-report', $attempt);

        $attempt->load([
            'test.sections.questions',
            'answers.question',
            'invitation'
        ]);

        $pdf = Pdf::loadView('reports.attempt', compact('attempt'));
        return $pdf->download('report-' . $attempt->id . '.pdf');
    }

    public function exportCsv(Attempt $attempt)
    {
        $this->authorize('export-report', $attempt);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report-' . $attempt->id . '.csv"',
        ];

        $callback = function () use ($attempt) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Question', 'Type', 'Candidate Answer', 'Marks Awarded', 'Max Marks', 'Correct']);

            foreach ($attempt->answers as $answer) {
                fputcsv($handle, [
                    $answer->question->description,
                    $answer->question->type,
                    is_array($answer->answer_json) ? json_encode($answer->answer_json) : $answer->answer_json,
                    $answer->marks_awarded ?? ($answer->is_correct ? ($answer->question->pivot->marks ?? $answer->question->marks_default) : 0),
                    $answer->question->pivot->marks ?? $answer->question->marks_default,
                    $answer->is_correct ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}