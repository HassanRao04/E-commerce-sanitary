<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\DeletedRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeletedRecordController extends Controller
{
    public function __construct(private readonly DeletedRecordService $deletedRecords) {}

    public function index(Request $request): View
    {
        $this->authorize('records.view');

        return view('admin.deleted-records.index', [
            'records' => $this->deletedRecords->search($request),
            'entityTypes' => $this->deletedRecords->entityTypeOptions(),
            'staffUsers' => User::query()->staff()->orderBy('name')->get(['id', 'name', 'first_name', 'last_name', 'email']),
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorize('records.restore');

        $this->deletedRecords->restore($type, $id, $request->user());

        return redirect()
            ->route('admin.deleted-records.index')
            ->with('success', 'Record restored successfully.');
    }
}
