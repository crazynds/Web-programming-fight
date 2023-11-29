@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Submit Code:
            </b>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" action="{{route('submitrun.store')}}">
        @csrf
        <div class="row">
            <div class="col">
                <label for="problem" class="form-label">Problem: </label><br />
                <select name="problem" class="form-select" required>
                    @foreach (App\Models\Problem::visible() as $problem)
                        <option value="{{ $problem->id }}" @if(isset($selected) && $problem->id==$selected) selected @endif >#{{ $problem->id }} - {{ $problem->title }}</option>
                    @endforeach
                </select>
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
        <!-- upload of a single file -->
        <div class="row">
            <div class="col">
                <label for="code" class="form-label">Select code: </label><br />
                <input type="file" class="form-control" name="code" required />
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
            <input type="submit" class="btn btn-primary" />
        </p>
    </form>

@endsection
