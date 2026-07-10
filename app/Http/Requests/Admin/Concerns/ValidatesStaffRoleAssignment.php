<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\StaffRole;
use App\Models\User;
use App\Services\Admin\RoleAssignmentService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesStaffRoleAssignment
{
    /**
     * @return array<string, mixed>
     */
    protected function staffRoleRules(bool $required = true): array
    {
        $rules = [
            'string',
            Rule::enum(StaffRole::class),
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return ['role' => $rules];
    }

    protected function validateStaffRoleAssignment(Validator $validator, ?User $target = null): void
    {
        if ($validator->errors()->isNotEmpty() || ! $this->filled('role')) {
            return;
        }

        try {
            app(RoleAssignmentService::class)->validateAssignment(
                $this->user(),
                $this->string('role')->toString(),
                $target,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        }
    }
}
