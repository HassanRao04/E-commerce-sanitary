@extends('layouts.admin')

@section('title', $message->subject.' — Contact Message')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Contact Message'])

    <p class="mb-4 text-sm text-gray-500">Received {{ $message->created_at?->format('F j, Y \a\t g:i A') }}</p>

    <div class="mb-4">
        <a href="{{ route('admin.inquiries.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← Back to messages</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-6 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">{{ $message->subject }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $message->referenceId() }}</p>
                    </div>
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
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Customer Name</dt>
                        <dd class="mt-1 text-slate-900">{{ $message->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Email</dt>
                        <dd class="mt-1">
                            <a href="mailto:{{ $message->email }}" class="text-indigo-600 hover:underline">{{ $message->email }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Phone Number</dt>
                        <dd class="mt-1 text-slate-900">{{ $message->phone ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Source</dt>
                        <dd class="mt-1 text-slate-900">{{ $message->sourceLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">IP Address</dt>
                        <dd class="mt-1 text-slate-900">{{ $message->ip_address ?: 'Not recorded' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Date</dt>
                        <dd class="mt-1 text-slate-900">{{ $message->created_at?->format('F j, Y \a\t g:i A') }}</dd>
                    </div>
                </dl>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Message</h3>
                    <div class="mt-2 rounded-lg bg-slate-50 ring-1 ring-slate-100 p-4 text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $message->message }}</div>
                </div>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-5 space-y-3">
                <h3 class="font-semibold text-slate-900">Actions</h3>

                @can('update', $message)
                    @if ($message->status === \App\Enums\InquiryStatus::New)
                        <form method="POST" action="{{ route('admin.inquiries.status', $message) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ \App\Enums\InquiryStatus::Pending->value }}">
                            <button type="submit" class="w-full rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">Reply Later</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.inquiries.status', $message) }}" class="space-y-2">
                        @csrf @method('PATCH')
                        <label for="status" class="block text-sm font-medium text-gray-700">Update status</label>
                        <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($message->status === $status)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Status</button>
                    </form>
                @endcan

                @can('delete', $message)
                    <form method="POST" action="{{ route('admin.inquiries.destroy', $message) }}" onsubmit="return confirm('Delete this inquiry permanently?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full rounded-md bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700">Delete Inquiry</button>
                    </form>
                @endcan
            </div>
        </aside>
    </div>
@endsection
