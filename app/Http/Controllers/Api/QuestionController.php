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
            ->with(['options', 'tags', 'creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return QuestionResource::collection($questions);
    }

    public function store(StoreQuestionRequest $request)
    {
        $validated = $request->validated();
        $validated['organization_id'] = $request->user()->organization_id;
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $question = Question::create($validated);

        if ($request->type === 'mcq' && $request->has('options')) {
            $question->options()->createMany($request->options);
        }

        if ($request->has('tags')) {
            $question->tags()->sync($request->tags);
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
        $validated['updated_by'] = $request->user()->id;

        $question->update($validated);

        if ($request->type === 'mcq' && $request->has('options')) {
            // Simple sync: delete old, add new
            $question->options()->delete();
            $question->options()->createMany($request->options);
        }

        if ($request->has('tags')) {
            $question->tags()->sync($request->tags);
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