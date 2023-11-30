<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirect($provider){
        if($provider!='github')return redirect('/');
        return Socialite::driver($provider)->scopes(['read:user'])->redirect();
    }

    public function callback($provider){
        if($provider!='github')return redirect('/');
        $user = Socialite::driver($provider)->user();
        
        $user = User::updateOrCreate([
            'provider_id' => $user->id
        ],[
            'name' => $user->name ?? $user->nickname,
            'email' => $user->email,
            'avatar' => $user->avatar,
            //'github_token' => $user->token,
            //'github_refresh_token' => $user->refreshToken,
        ]);
        
        Auth::login($user,true);
        
        return redirect()->route('user.me');
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('problem.index');
    }
}
