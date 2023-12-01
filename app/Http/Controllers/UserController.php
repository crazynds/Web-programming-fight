<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(){
        $users = User::with('lastRun:submit_runs.user_id,created_at')->get();
        return view('pages.user.index',[
            'users' => $users
        ]);
    }

    public function profile(){
        /** @var User */
        $user = Auth::user();
        return $this->profileUser($user);
    }

    public function profileUser(User $user){
        $problensCount = $user->submissions()->where('result','=',SubmitResult::Accepted)
            ->select('problem_id')->distinct()->count();

        $acceptedCount = $user->submissions()->where('result','=',SubmitResult::Accepted)
            ->select('problem_id','language')->distinct()->count();

        $attemptsCount = $user->submissions()->count();

        return view('pages.user.profile',[
            'user' => $user,
            'problems_count' => $problensCount,
            'accepted_count' => $acceptedCount,
            'attempts_count' => $attemptsCount,
        ]);
    }

    
}
