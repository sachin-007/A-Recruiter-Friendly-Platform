<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->role === 'super_admin';

        $questionQuery = Question::query()
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('organization_id', $user->organization_id));

        $testQuery = Test::query()
            ->when(! $isSuperAdmin, fn (Builder $query) => $query->where('organization_id', $user->organization_id));

        $invitationQuery = Invitation::query()
            ->whereHas('test', function (Builder $query) use ($isSuperAdmin, $user) {
                if (! $isSuperAdmin) {
                    $query->where('organization_id', $user->organization_id);
                }
            });

        $attemptQuery = Attempt::query()
            ->whereHas('test', function (Builder $query) use ($isSuperAdmin, $user) {
                if (! $isSuperAdmin) {
                    $query->where('organization_id', $user->organization_id);
                }
            });

        $counts = [
            'questions_count' => (clone $questionQuery)->count(),
            'tests_count' => (clone $testQuery)->count(),
            'invitations_count' => (clone $invitationQuery)->count(),
            'completed_attempts_count' => (clone $attemptQuery)->where('attempts.status', 'completed')->count(),
        ];

        if ($isSuperAdmin) {
            $counts['organizations_count'] = Organization::count();
            $counts['users_count'] = User::count();
        }

        $questionTypeOrder = ['mcq', 'coding', 'sql', 'free_text'];
        $questionTypeLabels = [
            'mcq' => 'MCQ',
            'coding' => 'Coding',
            'sql' => 'SQL',
            'free_text' => 'Free Text',
        ];

        $questionTypeCounts = (clone $questionQuery)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $difficultyOrder = ['easy', 'medium', 'hard'];
        $difficultyCounts = (clone $questionQuery)
            ->selectRaw('difficulty, COUNT(*) as total')
            ->groupBy('difficulty')
            ->pluck('total', 'difficulty');

        $invitationStatusOrder = ['sent', 'opened', 'started', 'completed'];
        $invitationStatusCounts = (clone $invitationQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $trendStart = now()->subDays(13)->startOfDay();
        $trendRaw = (clone $attemptQuery)
            ->where('attempts.status', 'completed')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$trendStart, now()->endOfDay()])
            ->get(['completed_at'])
            ->groupBy(fn (Attempt $attempt) => $attempt->completed_at?->toDateString())
            ->map(fn ($items) => $items->count());

        $trendLabels = [];
        $trendData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->toDateString();
            $trendLabels[] = $date->format('d M');
            $trendData[] = (int) ($trendRaw[$dateKey] ?? 0);
        }

        $topTests = (clone $testQuery)
            ->withCount([
                'invitations',
                'attempts as completed_attempts_count' => fn (Builder $query) => $query->where('attempts.status', 'completed'),
            ])
            ->orderByDesc('completed_attempts_count')
            ->orderByDesc('invitations_count')
            ->limit(6)
            ->get(['id', 'title']);

        $organizationOverview = collect();
        if ($isSuperAdmin) {
            $organizationOverview = Organization::query()
                ->withCount([
                    'users',
                    'tests',
                    'attempts as completed_attempts_count' => fn (Builder $query) => $query->where('attempts.status', 'completed'),
                ])
                ->orderByDesc('completed_attempts_count')
                ->orderByDesc('users_count')
                ->limit(8)
                ->get(['id', 'name']);
        }

        return response()->json([
            ...$counts,
            'charts' => [
                'questions_by_type' => [
                    'labels' => array_map(fn ($key) => $questionTypeLabels[$key], $questionTypeOrder),
                    'data' => array_map(fn ($key) => (int) ($questionTypeCounts[$key] ?? 0), $questionTypeOrder),
                ],
                'questions_by_difficulty' => [
                    'labels' => array_map('ucfirst', $difficultyOrder),
                    'data' => array_map(fn ($key) => (int) ($difficultyCounts[$key] ?? 0), $difficultyOrder),
                ],
                'invitations_by_status' => [
                    'labels' => array_map('ucfirst', $invitationStatusOrder),
                    'data' => array_map(fn ($key) => (int) ($invitationStatusCounts[$key] ?? 0), $invitationStatusOrder),
                ],
                'attempts_trend' => [
                    'labels' => $trendLabels,
                    'data' => $trendData,
                ],
                'top_tests' => [
                    'labels' => $topTests->pluck('title')
                        ->map(fn (?string $title) => Str::limit((string) $title, 28))
                        ->values(),
                    'invitations' => $topTests->pluck('invitations_count')->map(fn ($value) => (int) $value)->values(),
                    'completed_attempts' => $topTests->pluck('completed_attempts_count')->map(fn ($value) => (int) $value)->values(),
                ],
                'organizations_overview' => $isSuperAdmin ? [
                    'labels' => $organizationOverview->pluck('name')->values(),
                    'users' => $organizationOverview->pluck('users_count')->map(fn ($value) => (int) $value)->values(),
                    'tests' => $organizationOverview->pluck('tests_count')->map(fn ($value) => (int) $value)->values(),
                    'completed_attempts' => $organizationOverview->pluck('completed_attempts_count')->map(fn ($value) => (int) $value)->values(),
                ] : null,
            ],
        ]);
    }
}
