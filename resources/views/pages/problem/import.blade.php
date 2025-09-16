@extends('layouts.base')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Import a Problem:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('problem.index') }}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" enctype="multipart/form-data" method="post" action="{{ route('problem.upload') }}">
        @csrf

        <div class="row">
            <div class="col">
                <h3>
                    Zip Problem:
                </h3>
                <input type="file" id="file" name="file" style="max-width:100%" />
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

@section('script')
    <script type='module'></script>
@endsection
