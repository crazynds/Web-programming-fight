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
            <a href="{{ route('problem.scorer.index', ['problem' => $problem->id]) }}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
        action="{{ route('problem.scorer.store', ['problem' => $problem->id]) }}">
        @csrf

        <div class="row">
            <div class="col">
                <label for="name" class="form-label">Name: </label><br />
                <input type="text" class="form-control" name="name" required />
            </div>
        </div>
        <div class="row">
            <div class="col">
                <label for="code" class="form-label">Scorer code: </label><br />
                <input type="file" class="form-control" name="code" required />
            </div>
            <div class="col">
                <label for="lang" class="form-label">Language: </label><br />
                <select name="lang" class="form-select" required>
                    @foreach (App\Enums\LanguagesType::list() as $name => $code)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-4">
                <label for="input" class="form-label">Input test: </label><br />
                <input type="file" class="form-control" name="input" required />
            </div>
            <div class="col-4">
                <label for="memory_limit" class="form-label">Memory Limit (MB): </label><br />
                <input type="number" class="form-control" id="memory_limit" name="memory_limit"
                    value="{{ old('memory_limit', $problem->memory_limit) }}" />
            </div>
            <div class="col-4">
                <label for="time_limit" class="form-label">Time Limit (ms): </label><br />
                <input type="number" class="form-control" id="time_limit" name="time_limit"
                    value="{{ old('time_limit', $problem->time_limit) }}" />
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
    <script></script>
@endsection
