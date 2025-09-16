<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreForumRequest;
use App\Models\Forum;

class ForumController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Forum::class, 'forum');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $forums = Forum::orderBy('id')->get();

        return view('pages.forum.index', [
            'forums' => $forums,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->edit(new Forum);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreForumRequest $request)
    {
        $data = $request->safe()->except(['problems', 'g-recaptcha-response']);

        $forum = Forum::create($data);

        return redirect()->route('forum.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Forum $forum)
    {
        $forums = Forum::orderBy('id')->get();

        return view('pages.forum.show', [
            'forums' => $forums,
            'forum' => $forum,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Forum $forum)
    {
        return view('pages.forum.create', [
            'forum' => $forum,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreForumRequest $request, Forum $forum)
    {
        return redirect()->route('forum.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Forum $forum)
    {
        $forum->delete();

        return redirect()->route('forum.index');
    }
}
