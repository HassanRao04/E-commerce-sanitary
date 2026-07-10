<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?int $actorId = null,
    ): ActivityLog {
        $userAgent = request()->userAgent();

        return ActivityLog::create([
            'user_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'description' => $description ?? $this->buildDescription($action, $model),
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'browser' => $this->summarizeBrowser($userAgent),
            'user_agent' => $userAgent,
        ]);
    }

    private function buildDescription(string $action, ?Model $model): string
    {
        if ($model === null) {
            return str_replace('.', ' ', $action);
        }

        $label = class_basename($model);

        return sprintf('%s %s #%s', str_replace('.', ' ', $action), $label, $model->getKey());
    }

    private function summarizeBrowser(?string $userAgent): ?string
    {
        if (blank($userAgent)) {
            return null;
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/') => 'Safari',
            default => 'Unknown',
        };

        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown',
        };

        return "{$browser} on {$os}";
    }
}
