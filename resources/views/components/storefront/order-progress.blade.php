@props(['order'])

@php
    $workflow = app(\App\Services\OrderWorkflowService::class);
    $steps = $workflow->progressSteps();
    $currentSlug = (string) $order->status;
    $currentDefinition = $workflow->find($currentSlug);
    $isCancelled = $workflow->isCancelled($currentSlug);
    $isReturned = $workflow->isReturned($currentSlug);
    $stepSlugs = $steps->pluck('slug')->all();
    $currentIndex = (! $isCancelled && ! $isReturned)
        ? array_search($currentSlug, $stepSlugs, true)
        : false;

    if ($currentIndex === false && $currentDefinition?->show_in_progress && $steps->isNotEmpty()) {
        $currentIndex = $steps->search(fn ($step) => $step->sort_order > ($currentDefinition->sort_order ?? 0));
        if ($currentIndex === false) {
            $currentIndex = $steps->count() - 1;
        } else {
            $currentIndex = max(0, $currentIndex - 1);
        }
    }
@endphp

@if ($isCancelled)
    <div class="rounded-ds-lg border border-danger/20 bg-danger-soft px-4 py-3 text-sm text-danger">
        This order was cancelled.
    </div>
@elseif ($isReturned)
    <div class="rounded-ds-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
        This order was returned.
    </div>
@elseif ($steps->isEmpty())
    <div class="rounded-ds-lg border border-ink-100 bg-surface-muted px-4 py-3 text-sm text-ink-600">
        Current status: <strong>{{ $workflow->label($currentSlug) }}</strong>
    </div>
@else
    <div class="order-progress">
        <div class="hidden sm:flex items-center justify-between gap-1">
            @foreach ($steps as $index => $step)
                @php
                    $completed = $currentIndex !== false && $index <= $currentIndex;
                    $active = $currentIndex !== false && $index === $currentIndex;
                @endphp
                <div class="flex flex-1 min-w-0 flex-col items-center text-center">
                    <div @class([
                        'flex h-9 w-9 items-center justify-center rounded-full border-2 text-xs font-semibold transition-colors',
                        'border-success bg-success text-white' => $completed && ! $active,
                        'border-accent bg-accent text-white ring-4 ring-accent/20' => $active,
                        'border-ink-200 bg-surface text-ink-400' => ! $completed && ! $active,
                    ])>
                        @if ($completed && ! $active)
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>
                    <p @class([
                        'mt-2 text-xs font-medium leading-tight',
                        'text-ink' => $completed || $active,
                        'text-ink-400' => ! $completed && ! $active,
                    ])>
                        {{ $step->name }}
                    </p>
                </div>
                @if (! $loop->last)
                    <div @class([
                        'h-0.5 flex-1 min-w-[1rem] -mt-6',
                        $index < $currentIndex ? 'bg-success' : 'bg-ink-200',
                    ]) aria-hidden="true"></div>
                @endif
            @endforeach
        </div>

        <div class="sm:hidden space-y-3">
            @foreach ($steps as $index => $step)
                @php
                    $completed = $currentIndex !== false && $index <= $currentIndex;
                    $active = $currentIndex !== false && $index === $currentIndex;
                @endphp
                <div @class([
                    'flex items-center gap-3 rounded-ds px-3 py-2',
                    'bg-accent/5 border border-accent/20' => $active,
                    'opacity-50' => ! $completed && ! $active,
                ])>
                    <div @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                        'bg-success text-white' => $completed && ! $active,
                        'bg-accent text-white' => $active,
                        'bg-ink-100 text-ink-500' => ! $completed && ! $active,
                    ])>
                        @if ($completed && ! $active)
                            ✓
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>
                    <span class="text-sm font-medium text-ink">{{ $step->name }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
