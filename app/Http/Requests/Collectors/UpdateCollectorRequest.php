<?php

declare(strict_types=1);

namespace App\Http\Requests\Collectors;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateCollectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('collectors.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'commission_base' => $this->input('commission_base', 'payment_total'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
                Rule::unique('collectors', 'user_id')->ignore($this->route('collector')),
                Rule::notIn($this->adminUserIds($companyId)),
            ],
            'user_name' => ['nullable', 'string', 'max:150'],
            'user_email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($this->input('user_id')),
            ],
            'user_password' => ['nullable', 'string', 'min:8', 'max:150'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'commission_type' => ['required', Rule::in(['percentage', 'fixed', 'none'])],
            'commission_base' => ['required', Rule::in(['payment_total', 'principal_only'])],
            'commission_value' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($this->input('commission_type') === 'percentage', ['max:100']),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'loan_ids' => ['nullable', 'array'],
            'loan_ids.*' => [
                'integer',
                Rule::exists('loans', 'id')
                    ->where('company_id', $companyId)
                    ->whereIn('status', ['active', 'late']),
            ],
        ];
    }

    /**
     * @return array<int, int>
     */
    private function adminUserIds(int $companyId): array
    {
        $roleTable = config('permission.table_names.roles');
        $modelRoleTable = config('permission.table_names.model_has_roles');
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $modelKey = config('permission.column_names.model_morph_key');
        $teamKey = config('permission.column_names.team_foreign_key');

        return DB::table($modelRoleTable)
            ->join($roleTable, "{$roleTable}.id", '=', "{$modelRoleTable}.{$rolePivotKey}")
            ->where("{$roleTable}.name", 'Administrador')
            ->where("{$modelRoleTable}.{$teamKey}", $companyId)
            ->where("{$modelRoleTable}.model_type", User::class)
            ->pluck("{$modelRoleTable}.{$modelKey}")
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
