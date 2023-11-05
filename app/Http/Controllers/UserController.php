<?php

namespace App\Http\Controllers;

use App\Enums\SubmitResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function profile(){
        /** @var User */
        $user = Auth::user();

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
