<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Invitation;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'questions_count' => Question::where('organization_id', $user->organization_id)->count(),
            'tests_count' => Test::where('organization_id', $user->organization_id)->count(),
            'invitations_count' => Invitation::whereHas('test', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            })->count(),
            'completed_attempts_count' => Attempt::whereHas('test', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            })->where('status', 'completed')->count(),
        ]);
    }
}
