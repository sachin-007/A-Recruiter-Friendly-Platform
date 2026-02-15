<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttemptResource;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttemptController extends Controller
{
    public function show(Request $request, Attempt $attempt)
    {
        if (! $this->canAccessAttempt($request, $attempt)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $attempt->load(['test.sections.questions.options', 'answers.question']);

        return new AttemptResource($attempt);
    }

    public function start(Request $request, Attempt $attempt)
    {
        if (! $this->isAttemptOwner($request, $attempt)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Attempt already closed'], 422);
        }

        if (! $attempt->started_at) {
            $attempt->started_at = now();
            $attempt->save();
        }

        if ($attempt->invitation && $attempt->invitation->status !== 'completed') {
            $attempt->invitation->update([
                'status' => 'started',
                'started_at' => $attempt->started_at ?? now(),
            ]);
        }

        return response()->json(['message' => 'Attempt started']);
    }

    public function update(Request $request, Attempt $attempt)
    {
        if (! $this->isAttemptOwner($request, $attempt)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Attempt already closed'], 422);
        }

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
        if (! $this->isAttemptOwner($request, $attempt)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Attempt already submitted']);
        }

        $attempt->status = 'completed';
        $attempt->completed_at = now();
        $attempt->save();

        if ($attempt->invitation) {
            $attempt->invitation->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        // Auto-calculate MCQ scores
        $this->calculateScore($attempt);

        return response()->json(['message' => 'Test submitted successfully']);
    }

    public function grade(Request $request, Attempt $attempt)
    {
        if (! $this->isStaffFromSameOrg($request, $attempt)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*.id' => 'required|exists:attempt_answers,id',
            'answers.*.marks_awarded' => 'required|numeric|min:0',
        ]);

        foreach ($request->answers as $answerData) {
            $answer = AttemptAnswer::where('attempt_id', $attempt->id)->find($answerData['id']);
            if (! $answer) {
                continue;
            }

            $answer->marks_awarded = $answerData['marks_awarded'];
            $answer->reviewed_by = $request->user() instanceof User ? $request->user()->id : null;
            $answer->reviewed_at = now();
            $answer->save();
        }

        // Recalculate total score
        $this->calculateScore($attempt);

        return response()->json(['message' => 'Marks saved']);
    }

    private function calculateScore(Attempt $attempt)
    {
        $answers = $attempt->answers()->with('question.options')->get();
        $marksByQuestion = DB::table('test_section_question')
            ->join('test_sections', 'test_sections.id', '=', 'test_section_question.test_section_id')
            ->where('test_sections.test_id', $attempt->test_id)
            ->pluck('test_section_question.marks', 'test_section_question.question_id');

        $totalMarks = 0.0;
        $earnedMarks = 0.0;

        foreach ($answers as $answer) {
            $question = $answer->question;
            if (! $question) {
                continue;
            }

            $maxMarks = (float) ($marksByQuestion[$question->id] ?? $question->marks_default ?? 0);
            $totalMarks += $maxMarks;

            if ($question->type === 'mcq') {
                $selectedOptionIds = collect((array) $answer->answer_json)
                    ->map(fn ($id) => (string) $id)
                    ->filter()
                    ->sort()
                    ->values();

                $correctOptionIds = $question->options
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->sort()
                    ->values();

                $isCorrect = $selectedOptionIds->isNotEmpty()
                    && $selectedOptionIds->all() === $correctOptionIds->all();

                $answer->is_correct = $isCorrect;
                $answer->marks_awarded = $isCorrect ? $maxMarks : 0;
                $answer->save();

                if ($isCorrect) {
                    $earnedMarks += $maxMarks;
                }

                continue;
            }

            if ($answer->marks_awarded !== null) {
                $earnedMarks += (float) $answer->marks_awarded;
            }
        }

        $attempt->score_total = round($earnedMarks, 2);
        $attempt->score_percent = $totalMarks > 0 ? round(($earnedMarks / $totalMarks) * 100, 2) : 0;
        $attempt->save();
    }

    private function isAttemptOwner(Request $request, Attempt $attempt): bool
    {
        $tokenable = $request->user();
        return $tokenable instanceof Attempt && $tokenable->id === $attempt->id;
    }

    private function isStaffFromSameOrg(Request $request, Attempt $attempt): bool
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        return in_array($user->role, ['admin', 'recruiter'], true)
            && $user->organization_id === $attempt->test->organization_id;
    }

    private function canAccessAttempt(Request $request, Attempt $attempt): bool
    {
        return $this->isAttemptOwner($request, $attempt)
            || $this->isStaffFromSameOrg($request, $attempt);
    }
}
