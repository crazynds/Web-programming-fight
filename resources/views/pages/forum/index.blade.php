@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Categories:
            </b>
        </div>
        <div class="col">
            @can('create', \App\Models\Forum::class)
                <a style="float:right" href="{{ route('forum.create') }}">
                    <button>New +</button>
                </a>
            @endcan
        </div>
    </div>
    <div class="row">
        <div class="col-4">

            <table border="1">
                <thead>
                    <tr>
                        <th class="px-1"><b>#</b></th>
                        <th class="text-center"><b>Title</b></th>
                        <th class="text-center px-2"><b>Topics</b></th>
                        
                        @can('create', \App\Models\Forum::class)
                            <th style="text-align: end; px-2"><b>Actions</b></th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($forums as $forum)
                        <tr>
                            <td class="pr-2">
                                #{{ $forum->id }}
                            </td>
                            <td class="px-2 text-center">
                                <a href="{{ route('forum.show', $forum->id) }}">
                                    {{ $forum->name }}
                                </a>
                            </td>
                            <td class="px-2 text-center">
                                10??
                            </td>
                            @can('create', \App\Models\Forum::class)
                                <td class="px-2">
                                    <div class="hstack gap-1">
                                        <a href="{{ route('forum.edit', ['forum' => $forum->id]) }}" class="d-flex action-btn">
                                            <i class="las la-edit"></i>
                                        </a>
                                        <div class="vr"></div>
                                        <form action="{{ route('forum.destroy', ['forum' => $forum->id]) }}" method="POST">
                                            @method('DELETE')
                                            @csrf
                                            <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;">
                                                <i class="las la-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endcan
                        </tr>
                        <tr>
                            <td colspan="3">
                                <small>
                                {{ $forum->description }}
                                </small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="col-8">
            @yield('forumlist')
        </div>
    </div>
@endsection
