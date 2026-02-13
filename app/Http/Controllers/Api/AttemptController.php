<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttemptResource;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function show(Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        $attempt->load(['test.sections.questions.options', 'answers']);

        return new AttemptResource($attempt);
    }

    public function update(Request $request, Attempt $attempt)
    {
        $this->authorize('update', $attempt);

        $request->validate([
            'answers' => 'array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_json' => 'nullable',
            'time_remaining' => 'nullable|integer',
        ]);

        if ($request->has('answers')) {
            foreach ($request->answers as $answer) {
                AttemptAnswer::updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $answer['question_id'],
                    ],
                    [
                        'answer_json' => $answer['answer_json'] ?? null,
                    ]
                );
            }
        }

        if ($request->has('time_remaining')) {
            $attempt->time_remaining = $request->time_remaining;
            $attempt->save();
        }

        return response()->json(['message' => 'Progress saved']);
    }

    public function submit(Request $request, Attempt $attempt)
    {
        $this->authorize('submit', $attempt);

        $attempt->status = 'completed';
        $attempt->completed_at = now();
        $attempt->save();

        // Auto-calculate MCQ scores
        $this->calculateScore($attempt);

        return response()->json(['message' => 'Test submitted successfully']);
    }

    public function grade(Request $request, Attempt $attempt)
    {
        $this->authorize('grade', $attempt);

        $request->validate([
            'answers' => 'required|array',
            'answers.*.id' => 'required|exists:attempt_answers,id',
            'answers.*.marks_awarded' => 'required|numeric|min:0',
        ]);

        foreach ($request->answers as $answerData) {
            $answer = AttemptAnswer::find($answerData['id']);
            $answer->marks_awarded = $answerData['marks_awarded'];
            $answer->reviewed_by = auth()->id();
            $answer->reviewed_at = now();
            $answer->save();
        }

        // Recalculate total score
        $this->calculateScore($attempt);

        return response()->json(['message' => 'Marks saved']);
    }

    private function calculateScore(Attempt $attempt)
    {
        $answers = $attempt->answers()->with('question')->get();

        $totalMarks = 0;
        $earnedMarks = 0;

        foreach ($answers as $answer) {
            $maxMarks = $answer->question->pivot?->marks ?? $answer->question->marks_default;

            // For MCQ, is_correct is set at submission time
            if ($answer->is_correct !== null) {
                $earnedMarks += $answer->is_correct ? $maxMarks : 0;
            } elseif ($answer->marks_awarded !== null) {
                // Manual marking
                $earnedMarks += $answer->marks_awarded;
            }

            $totalMarks += $maxMarks;
        }

        $attempt->score_total = $earnedMarks;
        $attempt->score_percent = $totalMarks > 0 ? round(($earnedMarks / $totalMarks) * 100, 2) : 0;
        $attempt->save();
    }
}