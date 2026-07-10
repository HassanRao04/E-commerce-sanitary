<?php

namespace App\Services;

use App\Enums\UserActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ActivityLogQueryService
{
    /**
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function search(Request $request, int $perPage = 25): LengthAwarePaginator
    {
        $query = ActivityLog::query()->with('user');

        if ($request->filled('action')) {
            $query->forAction($request->string('action')->toString());
        }

        if ($request->filled('actor')) {
            $query->forUser((int) $request->input('actor'));
        }

        if ($request->filled('subject')) {
            $query->where('model_type', User::class)
                ->where('model_id', (int) $request->input('subject'));
        }

        if ($request->boolean('user_events_only')) {
            $query->whereIn('action', UserActivityAction::values());
        }

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($builder) use ($term): void {
                $builder->where('description', 'like', "%{$term}%")
                    ->orWhere('action', 'like', "%{$term}%")
                    ->orWhere('ip_address', 'like', "%{$term}%");
            });
        }

        return $query->latest('created_at')->paginate($perPage)->withQueryString();
    }
}
