@extends('layouts.admin')

@section('title', 'Contact Messages')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Contact Messages',
        'subtitle' => $newCount > 0 ? "{$newCount} new" : 'Customer inquiries from the storefront contact form',
    ])

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 mb-6 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <label for="q" class="sr-only">Search</label>
                <input
                    id="q"
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search name, email, phone, subject, message..."
                    class="w-full rounded-md border-gray-300 shadow-sm text-sm"
                >
            </div>
            <div>
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date" class="sr-only">Date</label>
                <input
                    id="date"
                    type="date"
                    name="date"
                    value="{{ request('date') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm text-sm"
                >
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-slate-900 text-white rounded-md text-sm font-medium hover:bg-slate-800">
                    Filter
                </button>
                @if (request()->filled('q') || request()->filled('status') || request()->filled('date'))
                    <a href="{{ route('admin.inquiries.index') }}" class="px-4 py-2 rounded-md text-sm font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Reference</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Subject</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Phone</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($messages as $message)
                        <tr @class(['bg-indigo-50/40' => $message->status === \App\Enums\InquiryStatus::New])>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $message->referenceId() }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.inquiries.show', $message) }}" class="font-medium text-slate-900 hover:text-indigo-700">
                                    {{ $message->name }}
                                </a>
                                <p class="text-gray-500 truncate max-w-[14rem]">{{ $message->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800 truncate max-w-[16rem]">{{ $message->subject }}</p>
                                <p class="text-gray-500 truncate max-w-[16rem]">{{ \Illuminate\Support\Str::limit($message->message, 80) }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $message->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                {{ $message->created_at?->format('M j, Y') }}
                                <span class="block text-xs">{{ $message->created_at?->format('g:i A') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-blue-50 text-blue-700' => $message->status === \App\Enums\InquiryStatus::New,
                                    'bg-amber-50 text-amber-700' => $message->status === \App\Enums\InquiryStatus::Pending,
                                    'bg-emerald-50 text-emerald-700' => $message->status === \App\Enums\InquiryStatus::Replied,
                                    'bg-slate-100 text-slate-600' => $message->status === \App\Enums\InquiryStatus::Closed,
                                    'bg-rose-50 text-rose-700' => $message->status === \App\Enums\InquiryStatus::Spam,
                                ])>
                                    {{ $message->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-x-3 gap-y-1">
                                    <a href="{{ route('admin.inquiries.show', $message) }}" class="text-indigo-600 hover:text-indigo-800">View</a>

                                    @can('delete', $message)
                                        <form method="POST" action="{{ route('admin.inquiries.destroy', $message) }}" class="inline" onsubmit="return confirm('Delete this inquiry permanently?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-rose-700 hover:text-rose-900">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">No contact messages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($messages->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $messages->links() }}</div>
        @endif
    </div>
@endsection
