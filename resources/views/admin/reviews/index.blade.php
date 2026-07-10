@extends('layouts.admin')

@section('title', 'Reviews')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Product Reviews'])

    <div class="mb-4 flex flex-wrap gap-3">
        <a href="{{ route('admin.reviews.settings') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Review settings →</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search reviews..."
                class="rounded-md border-gray-300 shadow-sm text-sm">
            <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Enums\ReviewStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-md text-sm">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Rating</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reviews as $review)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $review->product?->name }}</p>
                            <p class="text-gray-500 truncate max-w-xs">{{ $review->title ?: $review->excerpt }}</p>
                            @if ($review->is_featured)
                                <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Featured</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $review->user?->name }}</td>
                        <td class="px-4 py-3">{{ $review->stars }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $review->created_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3 capitalize">{{ $review->status->value }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('moderate', $review)
                                <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                    @if ($review->status !== \App\Enums\ReviewStatus::Approved)
                                        <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="inline">@csrf @method('PATCH')<button class="text-green-600 hover:text-green-800">Approve</button></form>
                                    @endif
                                    @if ($review->status !== \App\Enums\ReviewStatus::Rejected)
                                        <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="inline">@csrf @method('PATCH')<button class="text-red-600 hover:text-red-800">Reject</button></form>
                                    @endif
                                    @if ($review->status !== \App\Enums\ReviewStatus::Hidden)
                                        <form method="POST" action="{{ route('admin.reviews.hide', $review) }}" class="inline">@csrf @method('PATCH')<button class="text-gray-600 hover:text-gray-800">Hide</button></form>
                                    @endif
                                    @if ($review->status === \App\Enums\ReviewStatus::Approved)
                                        <form method="POST" action="{{ route('admin.reviews.feature', $review) }}" class="inline">@csrf @method('PATCH')<button class="text-indigo-600 hover:text-indigo-800">{{ $review->is_featured ? 'Unfeature' : 'Feature' }}</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="inline" onsubmit="return confirm('Delete this review permanently?')">@csrf @method('DELETE')<button class="text-rose-700 hover:text-rose-900">Delete</button></form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No reviews yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($reviews->hasPages())
            <div class="px-4 py-3 border-t">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection
