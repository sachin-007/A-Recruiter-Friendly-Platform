<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestRequest;
use App\Http\Requests\UpdateTestRequest;
use App\Http\Resources\TestResource;
use App\Http\Resources\TestSectionResource;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Test::class);

        $tests = Test::query()
            ->where('organization_id', $request->user()->organization_id)
            ->with(['creator', 'sections'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return TestResource::collection($tests);
    }

    public function store(StoreTestRequest $request)
    {
        $this->authorize('create', Test::class);

        $test = Test::create([
            'organization_id' => $request->user()->organization_id,
            'title' => $request->title,
            'description' => $request->description,
            'duration_minutes' => $request->duration_minutes ?? 0,
            'instructions' => $request->instructions,
            'status' => $request->status ?? 'draft',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return new TestResource($test);
    }

    public function show(Test $test)
    {
        $this->authorize('view', $test);
        $test->load(['sections.questions.options', 'creator', 'updater']);
        return new TestResource($test);
    }

    public function update(UpdateTestRequest $request, Test $test)
    {
        $this->authorize('update', $test);
        $test->update($request->validated() + ['updated_by' => $request->user()->id]);
        return new TestResource($test);
    }

    public function destroy(Test $test)
    {
        $this->authorize('delete', $test);
        $test->delete();
        return response()->json(['message' => 'Test deleted']);
    }

    public function publish(Test $test)
    {
        $this->authorize('publish', $test);
        $test->update(['status' => 'published']);
        return response()->json(['message' => 'Test published']);
    }

    public function archive(Test $test)
    {
        $this->authorize('publish', $test);
        $test->update(['status' => 'archived']);
        return response()->json(['message' => 'Test archived']);
    }

    // --- Section Management ---
    public function addSection(Request $request, Test $test)
    {
        $this->authorize('update', $test);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $section = $test->sections()->create([
            'title' => $request->title,
            'description' => $request->description,
            'order' => $request->order ?? ($test->sections()->max('order') + 1),
        ]);

        return new TestSectionResource($section);
    }

    public function updateSection(Request $request, Test $test, TestSection $section)
    {
        $this->authorize('update', $test);
        $section->update($request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order' => 'sometimes|integer',
        ]));
        return new TestSectionResource($section);
    }

    public function deleteSection(Test $test, TestSection $section)
    {
        $this->authorize('update', $test);
        $section->delete();
        return response()->json(['message' => 'Section deleted']);
    }

    // --- Question Attachment ---
    public function attachQuestion(Request $request, TestSection $section)
    {
        $this->authorize('update', $section->test);

        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'marks' => 'required|integer|min:1',
            'order' => 'nullable|integer',
            'is_optional' => 'boolean',
        ]);

        $question = Question::findOrFail($request->question_id);
        // Ensure question belongs to same organization
        if ($question->organization_id !== $section->test->organization_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $section->questions()->attach($request->question_id, [
            'marks' => $request->marks,
            'order' => $request->order ?? ($section->questions()->max('order') + 1),
            'is_optional' => $request->is_optional ?? false,
        ]);

        return response()->json(['message' => 'Question attached']);
    }

    public function updateQuestionPivot(Request $request, TestSection $section, Question $question)
    {
        $this->authorize('update', $section->test);

        $request->validate([
            'marks' => 'sometimes|integer|min:1',
            'order' => 'sometimes|integer',
            'is_optional' => 'sometimes|boolean',
        ]);

        $section->questions()->updateExistingPivot($question->id, $request->only(['marks', 'order', 'is_optional']));

        return response()->json(['message' => 'Question updated']);
    }

    public function detachQuestion(TestSection $section, Question $question)
    {
        $this->authorize('update', $section->test);
        $section->questions()->detach($question->id);
        return response()->json(['message' => 'Question removed']);
    }
}