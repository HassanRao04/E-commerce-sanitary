<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Http\Requests\Admin\Concerns\ValidatesStaffRoleAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use ValidatesStaffRoleAssignment;

    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], $this->staffRoleRules());
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please select a role.',
            'role.enum' => 'The selected role is invalid.',
            'status.required' => 'Please select a status.',
            'profile_photo.image' => 'Profile image must be a valid image file.',
            'profile_photo.max' => 'Profile image must not exceed 2 MB.',
        ];
    }

    public function after(): array
    {
        return [
            fn ($validator) => $this->validateStaffRoleAssignment($validator),
        ];
    }
}
