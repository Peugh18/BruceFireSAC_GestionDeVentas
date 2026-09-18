<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRolePermissionsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display the system's roles with how many permissions/users each has.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return Inertia::render('administration/roles/index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Display every system permission, grouped by module, with checkboxes
     * for which ones this role currently has.
     */
    public function show(Role $role): Response
    {
        $this->authorize('view', $role);

        $role->load('permissions:id,name');

        $groups = Permission::query()
            ->orderBy('name')
            ->get(['name'])
            ->groupBy(fn (Permission $permission) => strstr($permission->name, '.', true) ?: $permission->name)
            ->map(fn ($permissions) => $permissions->pluck('name')->values())
            ->sortKeys();

        return Inertia::render('administration/roles/show', [
            'role' => $role,
            'permissionGroups' => $groups,
            'rolePermissions' => $role->permissions->pluck('name'),
        ]);
    }

    /**
     * Replace the role's permission set.
     */
    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $role->syncPermissions($request->validated('permissions', []));

        return back()->with('status', 'Permisos del rol actualizados correctamente.');
    }
}
