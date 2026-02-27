<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setup\CreateInitialAdminRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function show(): View
    {
        return view('setup');
    }

    public function store(CreateInitialAdminRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('super-admin');
        }

        Auth::login($user);

        return redirect('/dashboard');
    }
}
