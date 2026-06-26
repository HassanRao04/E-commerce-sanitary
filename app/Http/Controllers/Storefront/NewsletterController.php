<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\NewsletterRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;

class NewsletterController extends Controller
{
    public function store(NewsletterRequest $request): RedirectResponse
    {
        $email = strtolower($request->validated('email'));

        $subscriber = NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $email],
            ['subscribed_at' => now()],
        );

        $message = $subscriber->wasRecentlyCreated
            ? 'You\'re in! Check your inbox for your welcome offer.'
            : 'You\'re already subscribed — thanks for being part of our community.';

        return back()->with('newsletter_success', $message);
    }
}
