<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Models\Problem;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Tag::class, 'tag');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tags = Tag::all();

        return view('pages.tag.index', [
            'tags' => $tags,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->edit(new Tag);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTagRequest $request)
    {
        $data = $request->safe()->except(['problems', 'g-recaptcha-response']);
        DB::beginTransaction();
        /** @var Tag */
        $tag = Tag::create($data);
        $tag->problems()->detach();
        foreach ($request->input('problems') as $key => $id) {
            $tag->problems()->attach($id);
        }
        DB::commit();

        return redirect()->route('tag.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        return redirect()->route('problem.index', ['tag' => $tag->id]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tag $tag)
    {
        return view('pages.tag.create', [
            'tag' => $tag,
            'problems' => Problem::whereNull('problems.online_judge')
                ->where(function ($query) {
                    /** @var User */
                    $user = Auth::user();
                    if (! $user->isAdmin()) {
                        $query->where('user_id', $user->id)
                            ->orWhere('visible', true);
                    }
                })
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTagRequest $request, Tag $tag)
    {
        $data = $request->safe()->except(['problems', 'g-recaptcha-response']);
        DB::beginTransaction();
        $tag->update($data);
        $tag->problems()->detach();
        foreach ($request->input('problems') as $key => $id) {
            $tag->problems()->attach($id);
        }
        DB::commit();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()->route('tag.index');
    }
}
