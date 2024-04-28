@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Scorers:
            </b>
        </div>
        <div class="col" style="text-align:center;">
            <h1>
                {{ $problem->title }}
            </h1>
        </div>
        <div class="col">
            <a style="float:right" href="{{ route('problem.scorer.create', ['problem' => $problem->id]) }}">
                <button>New +</button>
            </a>
            <a style="float:right; margin-right: 10px;"
                href="{{ route('problem.scorer.reavaliate', ['problem' => $problem->id]) }}">
                <button>Reavaliate All Scorers</button>
            </a>
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

    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th class="text-center"><b>Name</b></th>
                <th class="text-center"><b>Limits</b></th>
                <th class="text-center"><b>Categories</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($scorers as $scorer)
                <tr>
                    <td class="pr-2">
                        #{{ $scorer->id }}
                    </td>
                    <td class="px-2">
                        {{ $scorer->name }}
                    </td>
                    <td class="px-2">
                        {{ $scorer->time_limit }}s / {{ $scorer->memory_limit }}MB
                    </td>
                    <td class="px-2">
                        {{ $scorer->categories }}
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            <form
                                action="{{ route('problem.scorer.destroy', ['problem' => $problem->id, 'scorer' => $scorer->id]) }}"
                                method="POST">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;">
                                    <i class="las la-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection
