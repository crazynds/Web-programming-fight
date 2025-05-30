@extends('layouts.boca')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Creating a Team:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('team.index') }}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
        action="@if (isset($team->id)) {{ route('team.update', ['team' => $team->id]) }}@else{{ route('team.store') }} @endif">
        @csrf
        @if (isset($team->id))
            @method('PUT')
        @endif
        <div class="row">
            <div class="col">
                <label for="name" class="form-label">Team name: </label><br />
                <input type="text" name="name" class="form-control" maxlength="40"
                    value="{{ old('name', $team->name) }}" />
            </div>
            <div class="col-3">
                <label for="acronym" class="form-label">Team acronym: </label><br />
                <input type="text" maxlength="5" name="acronym" class="form-control"
                    value="{{ old('acronym', $team->acronym) }}" />
            </div>
        </div>
        <div class="row mt-3">
            <div class="col" style="padding-top: 30px;">
                <input id="tags" name="membersjson" placeholder="Members nickname"
                    value="{{ old('membersjson', $team->membersjson()) }}">
                <small>
                    Write the member's github nickname and press enter. It is not necessary to add your own
                    nickname.</small>
            </div>
            <div class="col-3">
                <label for="institution_acronym" class="form-label">Institution acronym: </label><br />
                <input type="text" maxlength="6" name="institution_acronym" class="form-control"
                    value="{{ old('institution_acronym', $team->institution_acronym) }}" />
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

@section('head')
    {{-- <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" /> --}}

    {{-- <style>
    .tagify+input{
      display: block !important;
      position: static !important;
      transform: none !important;
      width: 90%;
      margin-top: 1em;
      padding: .5em;
    }
</style> --}}
@endsection

@section('script')
    <script type='module'>
        window.addEventListener("load", function() {
            var input = document.querySelector('#tags')
            var tagify = new Tagify(input, {

            })
            $('form').one('submit', function(e) {
                e.preventDefault();
                // do your things ...

                setTimeout(() => {
                    $(this).submit();
                }, 200);
            });
        })
    </script>
@endsection
