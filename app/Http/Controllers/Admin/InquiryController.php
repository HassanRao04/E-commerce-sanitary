<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateInquiryStatusRequest;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Inquiry::class);

        $messages = Inquiry::query()
            ->contactMessages()
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->createdOn($request->input('date'))
            ->recent()
            ->paginate(20)
            ->withQueryString();

        return view('admin.inquiries.index', [
            'messages' => $messages,
            'statuses' => InquiryStatus::filterable(),
            'newCount' => Inquiry::query()->contactMessages()->where('status', InquiryStatus::New)->count(),
        ]);
    }

    public function show(Inquiry $inquiry): View
    {
        $this->authorize('view', $inquiry);

        return view('admin.inquiries.show', [
            'message' => $inquiry,
            'statuses' => InquiryStatus::filterable(),
        ]);
    }

    public function updateStatus(UpdateInquiryStatusRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $status = InquiryStatus::from($request->validated('status'));

        $inquiry->updateStatus($status);

        return back()->with('success', 'Inquiry status updated to '.$status->label().'.');
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $this->authorize('delete', $inquiry);

        $inquiry->delete();

        return redirect()
            ->route('admin.inquiries.index')
            ->with('success', 'Inquiry deleted.');
    }
}
