<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesStaffRoleAssignment;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Http\FormRequest;

class AssignUserRoleRequest extends FormRequest
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

        return app(UserPolicy::class)->assignRole($actor, $target);
    }

    public function rules(): array
    {
        return $this->staffRoleRules();
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Please select a role.',
            'role.enum' => 'The selected role is invalid.',
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
