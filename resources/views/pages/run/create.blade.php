@extends('layouts.boca')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Submit Code:
            </b>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
        action="{{ route(($contestService->inContest ? 'contest.' : '') . 'submission.store') }}">
        @csrf
        <div class="row">
            <div class="col">
                <label for="problem" class="form-label">Problem: </label><br />
                <select name="problem" class="form-select select2" required>
                    @php($letter = 'A')
                    @foreach ($problems as $problem)
                        <option value="{{ $problem->id }}" @if (isset($selected) && $problem->id == $selected) selected @endif>
                            @if ($contestService->inContest)
                                {{ $letter++ }} - {{ $problem->title }}
                            @else
                                #{{ $problem->id }} - {{ $problem->title }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col">
                <label for="lang" class="form-label">Language: </label><br />
                <select name="lang" class="form-select select2" required>
                    @foreach (App\Enums\LanguagesType::enabled() as $name => $code)
                        @if (!$contestService->inContest || in_array($code, $contestService->contest->languages))
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endif
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
            <div class="row mt-3">
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

    <br>
    <table border="1" style="float:right">
        <thead>
            <tr>
                <th class="px-1">
                    Language
                </th>
                <th class="px-1">
                    Time multi.
                </th>
                <th class="px-1">
                    Memory multi.
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach (App\Enums\LanguagesType::enabled() as $name => $code)
                @if (!$contestService->inContest || in_array($code, $contestService->contest->languages))
                    @if (array_key_exists($name, App\Enums\LanguagesType::modifiers()))
                        <tr>
                            <td class="px-1">
                                {{ $name }}
                            </td>
                            <td class="text-center">
                                {{ App\Enums\LanguagesType::modifiers()[$name][0] }}x
                            </td>
                            <td class="text-center">
                                {{ App\Enums\LanguagesType::modifiers()[$name][1] }}x
                            </td>
                        </tr>
                    @endif
                @endif
            @endforeach
        </tbody>
    </table>
@endsection
