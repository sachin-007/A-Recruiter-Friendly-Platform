<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Question::class);

        $questions = Question::query()
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->difficulty, fn($q, $difficulty) => $q->where('difficulty', $difficulty))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->tag, fn($q, $tag) => $q->whereHas('tags', fn($t) => $t->where('tags.id', $tag)))
            ->when($request->created_by, function ($query) use ($request) {
                if ($request->created_by === 'me') {
                    $query->where('created_by', $request->user()->id);
                    return;
                }

                $query->where('created_by', $request->created_by);
            })
            ->with(['options', 'tags', 'creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return QuestionResource::collection($questions);
    }

    public function store(StoreQuestionRequest $request)
    {
        $validated = $request->validated();
        $options = $validated['options'] ?? [];
        $tagIds = $validated['tags'] ?? [];

        unset($validated['options'], $validated['tags']);

        $validated['organization_id'] = $request->user()->organization_id;
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $question = Question::create($validated);

        if ($question->type === 'mcq' && ! empty($options)) {
            $question->options()->createMany($options);
        }

        if ($request->has('tags')) {
            $question->tags()->sync($tagIds);
        }

        return new QuestionResource($question->load('options', 'tags', 'creator'));
    }

    public function show(Question $question)
    {
        $this->authorize('view', $question);
        return new QuestionResource($question->load('options', 'tags', 'creator', 'updater'));
    }

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $this->authorize('update', $question);

        $validated = $request->validated();
        $optionsProvided = array_key_exists('options', $validated);
        $options = $validated['options'] ?? [];
        $tagIds = $validated['tags'] ?? [];

        unset($validated['options'], $validated['tags']);

        $validated['updated_by'] = $request->user()->id;

        $question->update($validated);

        $resolvedType = $validated['type'] ?? $question->type;

        if ($resolvedType === 'mcq') {
            if ($optionsProvided) {
                // Simple sync: delete old, add new
                $question->options()->delete();
                $question->options()->createMany($options);
            }
        } else {
            // Non-MCQ questions should not retain option rows.
            $question->options()->delete();
        }

        if ($request->has('tags')) {
            $question->tags()->sync($tagIds);
        }

        return new QuestionResource($question->load('options', 'tags'));
    }

    public function destroy(Question $question)
    {
        $this->authorize('delete', $question);
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }
}
