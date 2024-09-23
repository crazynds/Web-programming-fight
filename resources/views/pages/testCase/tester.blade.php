@extends('layouts.boca')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection


@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Submit a Test Case:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('problem.testCase.index', ['problem' => $problem->id]) }}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
        action="{{ route('problem.testCase.store', ['problem' => $problem->id]) }}">
        @csrf

        <div class="row">
            <div class="col-6">
                <h3>
                    Arquivos de input
                </h3>
                <input type="file" id="files1" name="code" style="max-width:100%" />
            </div>
            <div class="col-6">
                <h3>
                    Arquivos de output
                </h3>
                <input type="file" id="files2" name="outputs[]" style="max-width:100%" multiple="multiple" />
                <ul class="list-group output-preview m-4"></ul>
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

        <div class="row">
            <strong>
                *Each input file need an output file with the same name to create a test case.
            </strong>
            <strong>
                *Test cases with the same name as an existent text case will be replace the old one.
            </strong>
        </div>

        <p class="mt-3">
            {!! htmlFormButton('Submit', [
                'class' => 'btn btn-primary',
            ]) !!}
        </p>
    </form>
@endsection
