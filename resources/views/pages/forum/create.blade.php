@extends('layouts.base')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Creating a Category:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('forum.index') }}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
        action="@if (isset($forum->id)) {{ route('forum.update', ['forum' => $forum->id]) }}@else{{ route('forum.store') }} @endif">
        @csrf
        @if (isset($forum->id))
            @method('PUT')
        @endif
        <div class="row">
            <div class="col">
                <label for="name" class="form-label">Title: </label><br />
                <input type="text" name="name" class="form-control" maxlength="40"
                    value="{{ old('name', $forum->name) }}" />
            </div>
            <div class="col-4">
                <label for="is_public" class="form-label">Public (Create Threads): </label><br />
                <input type='hidden' id="hidden_is_public" value='0' name='is_public'>
                <input type="checkbox" id="is_public" value='1' name="is_public"
                    @if (old('is_public', $forum->is_public)) checked @endif />
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label for="description" class="form-label">Description: </label><br />
                <input type="text" name="description" class="form-control" maxlength="40"
                    value="{{ old('description', $forum->description) }}" />
            </div>
        </div>

        @if ($errors->any())
            <div class="row p-3">
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif


        <p class="mt-3">
            {!! htmlFormButton('Submit', [
                'class' => 'btn btn-primary',
            ]) !!}
        </p>
    </form>

@endsection

