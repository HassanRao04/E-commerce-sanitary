<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUserActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($this->input('action') === 'delete') {
            return $user->hasPermissionTo('users.delete');
        }

        return $user->hasPermissionTo('users.update');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Select at least one user.',
            'user_ids.min' => 'Select at least one user.',
        ];
    }
}
