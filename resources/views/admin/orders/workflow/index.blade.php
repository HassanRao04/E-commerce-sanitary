@extends('layouts.admin')

@section('title', 'Order Workflow')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Order Workflow',
        'subtitle' => 'ERP controls every order status shown on the storefront.',
    ])

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-medium">Statuses</h2>
                <span class="text-xs text-gray-500">System statuses cannot be deleted</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Slug</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Progress</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Group</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Orders</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($statuses as $status)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-order-status-badge :status="$status->slug" />
                                        @if ($status->is_default)
                                            <span class="text-[10px] uppercase tracking-wide text-indigo-600 font-semibold">Default</span>
                                        @endif
                                        @if ($status->is_system)
                                            <span class="text-[10px] uppercase tracking-wide text-gray-400">System</span>
                                        @endif
                                    </div>
                                    @if ($status->description)
                                        <p class="text-xs text-gray-500 mt-1">{{ $status->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $status->slug }}</td>
                                <td class="px-4 py-3">{{ $status->show_in_progress ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3 capitalize">{{ $status->customer_group ? str_replace('_', ' ', $status->customer_group) : '—' }}</td>
                                <td class="px-4 py-3">{{ number_format($status->orders()->count()) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('update', $status)
                                        <button type="button" class="text-indigo-600 hover:underline" @click="$dispatch('open-status-editor', @js($status))">Edit</button>
                                    @endcan
                                    @can('delete', $status)
                                        <form method="POST" action="{{ route('admin.orders.workflow.destroy', $status) }}" class="inline" onsubmit="return confirm('Delete this custom status?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline ml-2">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6" x-data="orderWorkflowPanel(@js($badgeColors))" @open-status-editor.window="openEditor($event.detail)">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium mb-4" x-text="editing ? 'Edit Status' : 'Create Custom Status'"></h2>
                <form method="POST" x-bind:action="formAction">
                    @csrf
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PATCH">
                    </template>

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="name" value="Name" />
                            <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" x-model="form.name" required />
                        </div>
                        <div>
                            <x-input-label for="slug" value="Slug" />
                            <input id="slug" name="slug" type="text" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" x-model="form.slug" x-bind:disabled="editing && form.is_system" />
                            <p class="mt-1 text-xs text-gray-500">Leave blank to auto-generate from the name.</p>
                        </div>
                        <div>
                            <x-input-label for="description" value="Description" />
                            <textarea id="description" name="description" rows="2" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" x-model="form.description"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="badge_color" value="Badge color" />
                                <select id="badge_color" name="badge_color" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" x-model="form.badge_color">
                                    <template x-for="color in badgeColors" :key="color">
                                        <option x-bind:value="color" x-text="color"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="sort_order" value="Sort order" />
                                <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="block mt-1 w-full" x-model="form.sort_order" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="customer_group" value="Customer dashboard group" />
                            <select id="customer_group" name="customer_group" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" x-model="form.customer_group">
                                <option value="">None</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="delivered">Delivered</option>
                                <option value="excluded">Excluded from totals</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="show_in_progress" value="1" x-model="form.show_in_progress"> Show in progress</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_terminal" value="1" x-model="form.is_terminal"> Terminal</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_delivered" value="1" x-model="form.is_delivered"> Delivered</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_cancellation" value="1" x-model="form.is_cancellation"> Cancellation</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_return" value="1" x-model="form.is_return"> Return</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" x-model="form.is_active"> Active</label>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <x-primary-button x-text="editing ? 'Save changes' : 'Create status'"></x-primary-button>
                        <button type="button" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50" x-show="editing" @click="resetForm()">Cancel edit</button>
                    </div>
                </form>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-sm text-slate-600">
                <p class="font-medium text-slate-800 mb-2">How it works</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>New orders start on the default ERP status (Pending).</li>
                    <li>Statuses marked “Show in progress” appear on the storefront tracker.</li>
                    <li>Website badges and labels always read from these ERP definitions.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderWorkflowPanel', (badgeColors) => ({
            badgeColors,
            editing: false,
            form: {},
            formAction: @json(route('admin.orders.workflow.store')),
            init() {
                this.resetForm();
            },
            resetForm() {
                this.editing = false;
                this.formAction = @json(route('admin.orders.workflow.store'));
                this.form = {
                    name: '',
                    slug: '',
                    description: '',
                    badge_color: 'gray',
                    sort_order: 900,
                    customer_group: '',
                    show_in_progress: false,
                    is_terminal: false,
                    is_delivered: false,
                    is_cancellation: false,
                    is_return: false,
                    is_active: true,
                    is_system: false,
                };
            },
            openEditor(status) {
                this.editing = true;
                this.formAction = @json(route('admin.orders.workflow.update', ['orderStatus' => 0])).replace('/0', '/' + status.id);
                this.form = {
                    name: status.name,
                    slug: status.slug,
                    description: status.description || '',
                    badge_color: status.badge_color,
                    sort_order: status.sort_order,
                    customer_group: status.customer_group || '',
                    show_in_progress: !!status.show_in_progress,
                    is_terminal: !!status.is_terminal,
                    is_delivered: !!status.is_delivered,
                    is_cancellation: !!status.is_cancellation,
                    is_return: !!status.is_return,
                    is_active: !!status.is_active,
                    is_system: !!status.is_system,
                };
            },
        }));
    });
</script>
@endpush
