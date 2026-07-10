<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Http\FormRequest;

class RemoveUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $target */
        $target = $this->route('user');
        $actor = $this->user();

        if ($target === null || $actor === null) {
            return false;
        }

        return app(UserPolicy::class)->removeRole($actor, $target);
    }

    public function rules(): array
    {
        return [];
    }
}
