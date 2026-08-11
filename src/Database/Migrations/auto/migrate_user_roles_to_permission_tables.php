<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the legacy single `users.role` column onto spatie/laravel-permission's
 * tables.
 *
 * This file is intentionally not timestamped so that it always runs after the
 * application's own migrations — including the published permission tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        $rolesTable = config('permission.table_names.roles', 'roles');
        $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
        $usesTeams = (bool) config('permission.teams', false);

        // Without the permission tables there is nowhere to migrate the data
        // to, so leave the column in place rather than destroying it.
        if (! Schema::hasTable($rolesTable) || ! Schema::hasTable($pivotTable)) {
            return;
        }

        $guard = config('auth.defaults.guard', 'web');
        $userModel = config('filament-auth.user_model', 'App\Models\User');
        $morphClass = class_exists($userModel) ? (new $userModel)->getMorphClass() : $userModel;

        $legacy = DB::table('users')
            ->whereNotNull('role')
            ->select('id', 'role')
            ->get();

        foreach ($legacy->pluck('role')->unique()->filter() as $roleName) {
            $exists = DB::table($rolesTable)
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->exists();

            if (! $exists) {
                DB::table($rolesTable)->insert([
                    'name' => $roleName,
                    'guard_name' => $guard,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $roleIds = DB::table($rolesTable)
            ->where('guard_name', $guard)
            ->pluck('id', 'name');

        foreach ($legacy as $row) {
            if (blank($row->role) || ! isset($roleIds[$row->role])) {
                continue;
            }

            $roleId = $roleIds[$row->role];

            $already = DB::table($pivotTable)
                ->where('role_id', $roleId)
                ->where('model_type', $morphClass)
                ->where($morphKey, $row->id)
                ->exists();

            if ($already) {
                continue;
            }

            $record = [
                'role_id' => $roleId,
                'model_type' => $morphClass,
                $morphKey => $row->id,
            ];

            if ($usesTeams) {
                $record[$teamKey] = null;
            }

            DB::table($pivotTable)->insert($record);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable();
        });
    }
};
