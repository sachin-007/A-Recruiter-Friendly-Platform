<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->role, fn($q, $role) => $q->where('role', $role))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'is_active' => $request->is_active ?? true,
            'password' => Hash::make(Str::random(16)), // random password, they'll use OTP
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'target_type' => User::class,
            'target_id' => $user->id,
            'action' => 'create',
            'old_values' => null,
            'new_values' => $user->toArray(),
        ]);

        return new UserResource($user);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $old = $user->toArray();
        $user->update($request->validated());

        AuditLog::create([
            'user_id' => $request->user()->id,
            'target_type' => User::class,
            'target_id' => $user->id,
            'action' => 'update',
            'old_values' => $old,
            'new_values' => $user->toArray(),
        ]);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'target_type' => User::class,
            'target_id' => $user->id,
            'action' => 'delete',
            'old_values' => $user->toArray(),
            'new_values' => null,
        ]);

        return response()->json(['message' => 'User deleted']);
    }

    public function toggleActive(User $user)
    {
        $this->authorize('toggleActive', $user);

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(['message' => 'User status updated', 'is_active' => $user->is_active]);
    }
}