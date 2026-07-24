<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateInfluencerRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $target */
        $target = $this->route('influencer');
        $actor = $this->user();

        if ($target === null || $actor === null) {
            return false;
        }

        return app(UserPolicy::class)->update($actor, $target);
    }

    public function rules(): array
    {
        /** @var User $influencer */
        $influencer = $this->route('influencer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($influencer->id)],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'Phone is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'status.required' => 'Please select a status.',
            'profile_photo.image' => 'Profile image must be a valid image file.',
            'profile_photo.max' => 'Profile image must not exceed 2 MB.',
        ];
    }
}
