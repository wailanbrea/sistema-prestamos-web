<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Services\Users\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create((int) $request->user()->company_id, $request->validated(), (int) $request->user()->id);

        return redirect()
            ->route('settings.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function edit(Request $request, int $user): View
    {
        return view('users.edit', [
            'managedUser' => $this->userService->findForCompany((int) $request->user()->company_id, $user),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, int $user): RedirectResponse
    {
        $managedUser = $this->userService->findForCompany((int) $request->user()->company_id, $user);

        try {
            $this->userService->update($managedUser, $request->validated(), (int) $request->user()->id);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return redirect()
            ->route('settings.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }
}
