@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Creating a Problem:
            </b>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" action="/run">
        <!-- upload of a single file -->
        <div class="row">
            <div class="col">
                <label for="code" class="form-label">Select code: </label><br />
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
            {{ csrf_field() }}
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
