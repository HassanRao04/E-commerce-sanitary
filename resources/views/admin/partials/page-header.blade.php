@props(['title', 'permission' => null, 'actionLabel' => 'Create', 'actionRoute' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">{{ $title }}</h1>
    @if ($actionRoute && $permission)
        @can($permission)
            <a href="{{ $actionRoute }}" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-md hover:bg-slate-800">
                {{ $actionLabel }}
            </a>
        @endcan
    @endif
</div>
