<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class HiveAuthController extends Controller
{
    public function redirectToHive(): RedirectResponse
    {
        return Socialite::driver('hive')->redirect();
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        $hiveUser = Socialite::driver('hive')->user();
        $user = User::find($hiveUser->getId());

        if ($user !== null) {
            $user->update([
                'name' => $hiveUser->getName(),
                'email' => $hiveUser->getEmail(),
            ]);
        } else {
            $user = User::create([
                'id' => $hiveUser->getId(),
                'name' => $hiveUser->getName(),
                'email' => $hiveUser->getEmail(),
                'role' => User::count() === 0 ? 'admin' : 'operator',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(config('services.hive.base_url'));
    }
}
