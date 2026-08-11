@extends('layouts.storefront')

@section('title', 'Write a review — '.config('app.name'))

@section('content')
    <div class="ds-container ds-section">
        @include('storefront.partials.breadcrumbs', ['items' => [
            ['label' => 'My Account', 'url' => route('shop.account.dashboard')],
            ['label' => 'My Orders', 'url' => route('shop.account.orders.index')],
            ['label' => $order->order_number, 'url' => route('shop.account.orders.show', $order)],
            ['label' => 'Write review', 'url' => null],
        ]])

        <div class="flex flex-col lg:flex-row gap-8">
            <x-storefront.account-nav />

            <div class="flex-1 max-w-2xl">
                <h1 class="ds-heading-2 mb-2">Write a review</h1>
                <p class="ds-body-sm text-ink-500 mb-6">Order {{ $order->order_number }} · {{ $orderItem->product_name }}</p>

                <form method="POST" action="{{ route('shop.account.orders.review.store', [$order, $orderItem]) }}" enctype="multipart/form-data" class="ds-card ds-card-body space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-ink-700 mb-2">Rating</label>
                        <div class="review-rating-stars flex gap-2" role="radiogroup" aria-label="Rating">
                            @for ($star = 1; $star <= 5; $star++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $star }}" class="sr-only" @checked((int) old('rating') === $star) required>
                                    <span @class([
                                        'inline-flex text-2xl transition hover:scale-110',
                                        'text-amber-400' => $star <= (int) old('rating', 0),
                                        'text-ink-300' => $star > (int) old('rating', 0),
                                    ])>{{ $star <= (int) old('rating', 0) ? '★' : '☆' }}</span>
                                </label>
                            @endfor
                        </div>
                        @error('rating')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-medium text-ink-700">Review title</label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" maxlength="120"
                               class="mt-1 block w-full rounded-lg border-ink-200 shadow-sm focus:border-ink-900 focus:ring-ink-900"
                               placeholder="Summarize your experience">
                        @error('title')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-medium text-ink-700">Review message</label>
                        <textarea name="body" id="body" rows="5" required minlength="10" maxlength="2000"
                                  class="mt-1 block w-full rounded-lg border-ink-200 shadow-sm focus:border-ink-900 focus:ring-ink-900"
                                  placeholder="Tell other customers about quality, delivery, and installation.">{{ old('body') }}</textarea>
                        @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="images" class="block text-sm font-medium text-ink-700">Photos (optional, up to 3)</label>
                        <input type="file" name="images[]" id="images" accept="image/*" multiple
                               class="mt-1 block w-full text-sm text-ink-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-surface-muted file:font-medium">
                        @error('images')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        @error('images.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="ds-btn-primary">Submit review</button>
                        <a href="{{ route('shop.account.orders.show', $order) }}" class="ds-btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const container = document.querySelector('.review-rating-stars');
            if (!container) {
                return;
            }

            const inputs = Array.from(container.querySelectorAll('input[name="rating"]'));

            function selectedValue() {
                const checked = inputs.find(function (input) {
                    return input.checked;
                });

                return checked ? parseInt(checked.value, 10) : 0;
            }

            function renderStars(activeValue) {
                inputs.forEach(function (input) {
                    const value = parseInt(input.value, 10);
                    const span = input.nextElementSibling;
                    const filled = value <= activeValue;

                    span.textContent = filled ? '★' : '☆';
                    span.classList.toggle('text-amber-400', filled);
                    span.classList.toggle('text-ink-300', !filled);
                });
            }

            inputs.forEach(function (input) {
                const label = input.closest('label');
                if (!label) {
                    return;
                }

                label.addEventListener('click', function () {
                    renderStars(parseInt(input.value, 10));
                });

                input.addEventListener('change', function () {
                    renderStars(parseInt(this.value, 10));
                });

                label.addEventListener('mouseenter', function () {
                    renderStars(parseInt(input.value, 10));
                });
            });

            container.addEventListener('mouseleave', function () {
                renderStars(selectedValue());
            });

            renderStars(selectedValue());
        })();
    </script>
@endsection
