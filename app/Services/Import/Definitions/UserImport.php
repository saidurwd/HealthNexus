<?php

namespace App\Services\Import\Definitions;

use App\Models\User;
use App\Services\Import\ImportContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Imported staff accounts are created INACTIVE with a random password nobody knows: no credential is ever written to a file or
 * shown on screen. An administrator activates each account and sends the password-reset link.
 */
class UserImport extends BaseImport
{
    public function key(): string
    {
        return 'users';
    }

    public function title(): string
    {
        return 'Staff accounts';
    }

    public function permission(): string
    {
        return 'import.users';
    }

    public function columns(): array
    {
        return [
            ['name' => 'name', 'required' => true, 'example' => 'Dr. Nasrin Akter'],
            ['name' => 'email', 'required' => true, 'example' => 'nasrin.akter@example.org'],
            ['name' => 'role', 'required' => true, 'example' => 'doctor', 'help' => 'An existing role name'],
            ['name' => 'phone', 'required' => false, 'example' => '01712345678'],
        ];
    }

    public function validate(array $raw, ImportContext $context): array
    {
        $errors = [];
        $email = $this->required($raw, 'email', $errors);
        $email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL) && $errors[] = 'email is not a valid address. Example: name@example.com.';
        $role = $this->required($raw, 'role', $errors, 100);
        $role && ! Role::query()->where('name', $role)->where('guard_name', 'web')->exists() && $errors[] = "role [$role] does not exist.";

        return ['errors' => $errors, 'data' => [
            'name' => $this->required($raw, 'name', $errors),
            'email' => $email ? strtolower($email) : null,
            'role' => $role,
            'phone' => $this->text($raw, 'phone'),
        ]];
    }

    public function fileKey(array $data): ?string
    {
        return $data['email'];
    }

    public function findExisting(array $data, ImportContext $context): ?string
    {
        return User::withTrashed()->where('email', $data['email'])->exists() ? "an account with e-mail {$data['email']}" : null;
    }

    public function commit(array $data, ImportContext $context): array
    {
        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
            'password' => Hash::make(Str::random(48)), 'is_active' => false,
        ]);
        $user->organizations()->attach($context->organizationId, ['access_level' => 'staff']);
        $context->branchId && $user->branches()->attach($context->branchId, ['access_level' => 'staff', 'organization_id' => $context->organizationId]);
        $user->assignRole($data['role']);

        return ['type' => User::class, 'id' => $user->id];
    }

    public function rollback(string $type, int $id, ImportContext $context): string
    {
        $user = User::find($id);
        if (! $user) {
            return 'removed';
        }

        if ($user->last_login_at === null && ! DB::table('audit_logs')->where('user_id', $id)->exists()) {
            $user->delete();

            return 'removed';
        }

        $user->forceFill(['is_active' => false])->save();

        return 'deactivated';
    }
}
