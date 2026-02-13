<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Tag::class);
        return TagResource::collection(Tag::orderBy('name')->get());
    }

    public function store(StoreTagRequest $request)
    {
        $this->authorize('create', Tag::class);
        $tag = Tag::create($request->validated());
        return new TagResource($tag);
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $this->authorize('update', $tag);
        $tag->update($request->validated());
        return new TagResource($tag);
    }

    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);
        $tag->delete();
        return response()->json(['message' => 'Tag deleted']);
    }
}