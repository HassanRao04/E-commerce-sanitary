<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogQueryService $activityLogs) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        return view('admin.activity.index', [
            'logs' => $this->activityLogs->search($request),
            'actions' => \App\Enums\UserActivityAction::cases(),
            'staffUsers' => User::query()->staff()->orderBy('name')->get(['id', 'name', 'first_name', 'last_name', 'email']),
        ]);
    }
}
