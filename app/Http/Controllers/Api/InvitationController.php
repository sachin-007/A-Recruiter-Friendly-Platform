<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Requests\BulkInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Mail\TestInvitationMail;
use App\Models\Invitation;
use App\Models\Test;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Invitation::class);

        $invitations = Invitation::query()
            ->whereHas('test', fn($q) => $q->where('organization_id', auth()->user()->organization_id))
            ->with(['test', 'creator', 'attempt'])
            ->orderBy('created_at', 'desc')
            ->paginate();

        return InvitationResource::collection($invitations);
    }

    public function store(StoreInvitationRequest $request)
    {
        $this->authorize('create', Invitation::class);

        $test = Test::findOrFail($request->test_id);
        $this->authorize('view', $test); // ensure user can invite to this test

        $invitation = Invitation::create([
            'test_id' => $test->id,
            'candidate_email' => $request->candidate_email,
            'candidate_name' => $request->candidate_name,
            'token' => Str::random(64),
            'status' => 'sent',
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
            'created_by' => auth()->id(),
        ]);

        $frontendUrl = config('app.frontend_url') . '/test/' . $invitation->token;
        Mail::to($invitation->candidate_email)->send(
            new TestInvitationMail($invitation->loadMissing('test.organization'), $frontendUrl)
        );

        return new InvitationResource($invitation);
    }

    public function bulkStore(BulkInvitationRequest $request)
    {
        $this->authorize('createBulk', Invitation::class);

        $test = Test::findOrFail($request->test_id);
        $this->authorize('view', $test);

        $invitations = [];
        foreach ($request->candidates as $candidate) {
            $invitation = Invitation::create([
                'test_id' => $test->id,
                'candidate_email' => $candidate['email'],
                'candidate_name' => $candidate['name'] ?? null,
                'token' => Str::random(64),
                'status' => 'sent',
                'sent_at' => now(),
                'expires_at' => now()->addDays(7),
                'created_by' => auth()->id(),
            ]);

            $frontendUrl = config('app.frontend_url') . '/test/' . $invitation->token;
            Mail::to($invitation->candidate_email)->send(
                new TestInvitationMail($invitation->loadMissing('test.organization'), $frontendUrl)
            );

            $invitations[] = $invitation;
        }

        return InvitationResource::collection(collect($invitations));
    }

    public function show(Invitation $invitation)
    {
        $this->authorize('view', $invitation);
        return new InvitationResource($invitation->load('test', 'creator', 'attempt'));
    }

    public function destroy(Invitation $invitation)
    {
        $this->authorize('delete', $invitation);
        $invitation->delete();
        return response()->json(['message' => 'Invitation revoked']);
    }
}
