<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'changeUser']);
    }

    public function redirect($provider)
    {
        if ($provider != 'github') {
            return redirect('/');
        }

        return Socialite::driver($provider)->scopes(['read:user'])->redirect();
    }

    public function callback($provider)
    {
        if ($provider != 'github') {
            return redirect('/');
        }
        $user = Socialite::driver($provider)->user();
        $user = User::updateOrCreate([
            'provider_id' => $user->id,
        ], [
            'name' => $user->nickname ?? $user->name,
            'email' => Str::lower($user->email),
            'avatar' => $user->avatar,
            'url' => $user->user['html_url'] ?? null,
            // 'github_token' => $user->token,
            // 'github_refresh_token' => $user->refreshToken,
        ]);

        Auth::login($user, true);

        return redirect()->route('home');
    }

    public function changeUser()
    {
        $users = User::all();
        $users->shuffle();
        foreach ($users as $user) {
            if (! $user->isAdmin()) {
                Auth::login($user, true);

                return redirect()->route('user.profile', [
                    'user' => $user->id,
                ]);
            }
        }

        return redirect()->route('home');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('problem.index');
    }
}
