@extends('layouts.base')

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
        action="{{ route('problem.testCase.store.manual', ['problem' => $problem->id]) }}">
        @csrf

        <div class="row">
            <div class="col-6">
                @if (!$testCase->name)
                    <label for="name" class="form-label">Title: </label><br />
                    <input type="text" class="form-control" id="name" name="name"
                        value="{{ old('name', $testCase->name) }}" />
                @else
                    <input type="hidden" class="form-control" id="name" name="name"
                        value="{{ old('name', $testCase->name) }}" />
                @endif
            </div>
            <div class="col-6">
                <label for="explanation" class="form-label">Explanation: </label><br />
                <textarea name="explanation" id="explanation" rows="8" style="width: 100%">{{ old('explanation', $testCase->explanation) }}</textarea>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <label for="name" class="form-label">Input: </label><br />
                <textarea name="input" id="input" rows="20" style="width: 100%">{{ old('input', $testCase->inputFile?->get()) }}</textarea>
            </div>
            <div class="col-6">
                <label for="name" class="form-label">Output: </label><br />
                <textarea name="output" id="output" rows="20" style="width: 100%">{{ old('output', $testCase->outputFile?->get()) }}</textarea>
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

@section('script')
@endsection
