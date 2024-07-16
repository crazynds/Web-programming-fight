<?php

namespace App\Http\Controllers\Contest;

use App\Http\Controllers\Controller;
use App\Http\Requests\AwnserClarificationRequest;
use App\Http\Requests\StoreClarificationRequest;
use App\Models\Contest;
use App\Models\ContestClatification;
use App\Services\ContestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClarificationController extends Controller
{

    public function __construct(protected ContestService $contestService)
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClarificationRequest $request)
    {
        $data = $request->safe()->all();
        $data['competitor_id'] = $this->contestService->competitor->id;
        $data['contest_id'] = $this->contestService->contest->id;

        $clarification = ContestClatification::create($data);

        return back()->with('success', 'Clarification created.');
    }

    public function update(AwnserClarificationRequest $request, Contest $contest, ContestClatification $clarification)
    {
        $data = $request->safe()->all();
        $clarification->update($data);
        return back()->with('success', 'Clarification answered.');
    }
}
