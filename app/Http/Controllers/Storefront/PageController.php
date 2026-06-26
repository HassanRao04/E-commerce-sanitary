<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactRequest;
use App\Models\Inquiry;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $page = Page::query()
            ->where('slug', 'about')
            ->where('is_published', true)
            ->first();

        return view('storefront.pages.about', compact('page'));
    }

    public function contact(): View
    {
        return view('storefront.pages.contact');
    }

    public function storeContact(ContactRequest $request): RedirectResponse
    {
        Inquiry::query()->create([
            ...$request->validated(),
            'type' => 'contact',
            'status' => InquiryStatus::New,
        ]);

        return redirect()
            ->route('shop.contact')
            ->with('success', 'Thank you! We received your message and will respond shortly.');
    }
}
