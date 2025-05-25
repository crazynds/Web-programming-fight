@extends('layouts.boca')

@section('head')
@endsection
@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Contests:
            </b>
        </div>
        <div class="col">
            {{-- @can('create', \App\Models\Tag::class)
                <a style="float:right" href="{{ route('tag.create') }}">
                    <button>New +</button>
                </a>
            @endcan --}}
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th class="px-1"><b>#</b></th>
                <th class="text-center"><b>Name</b></th>
                <th class="text-center px-2"><b>Problems</b></th>
                <th style="text-align: end; px-2"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tags as $tag)
                <tr>
                    <td class="pr-2">
                        #{{ $tag->id }}
                    </td>
                    <td class="px-2 text-center">
                        <a href="{{ route('tag.show', $tag->id) }}">
                            {{ $tag->name }}
                        </a>
                    </td>
                    <td class="px-2 text-center">
                        {{ $tag->problems()->count() }}
                    </td>
                    <td class="px-2">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
