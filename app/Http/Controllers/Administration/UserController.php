<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users with their assigned role(s).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->toString();
        $estado = $request->string('estado')->toString();

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($estado === 'activo', fn ($query) => $query->where('activo', true))
            ->when($estado === 'inactivo', fn ($query) => $query->where('activo', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('administration/users/index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * Store a newly created user, assigning a single role.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $plainPassword = $validated['password'] ?? Str::password(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($plainPassword),
            'activo' => $validated['activo'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        $status = 'Usuario creado correctamente.';
        if (! isset($validated['password'])) {
            $status .= " Contrasena generada: {$plainPassword}";
        }

        return back()->with('status', $status);
    }

    /**
     * Update the user's data, role and active status.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'activo' => $validated['activo'],
        ];

        if (! empty($validated['password'])) {
            $updates['password'] = Hash::make($validated['password']);
        }

        $user->update($updates);
        $user->syncRoles([$validated['role']]);

        return back()->with('status', 'Usuario actualizado correctamente.');
    }
}
