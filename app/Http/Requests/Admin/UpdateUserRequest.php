<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Http\Requests\Admin\Concerns\ValidatesStaffRoleAssignment;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    use ValidatesStaffRoleAssignment;

    public function authorize(): bool
    {
        /** @var User|null $target */
        $target = $this->route('user');
        $actor = $this->user();

        if ($target === null || $actor === null) {
            return false;
        }

        return app(UserPolicy::class)->update($actor, $target);
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return array_merge([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
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
        /** @var User $target */
        $target = $this->route('user');

        return [
            fn ($validator) => $this->validateStaffRoleAssignment($validator, $target),
        ];
    }
}
