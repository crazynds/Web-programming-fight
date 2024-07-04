@extends('layouts.boca')

@section('head')
@endsection
@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Competitors:
            </b>
        </div>
        <div class="col">
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th class="px-1"><b>#</b></th>
                <th class="text-center px-2"><b>Name</b></th>
                <th class="text-center px-2"><b>Score</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($competitors as $competitor)
                <tr>
                    <td>
                        {{ $competitor->acronym }}
                    </td>
                    <td class="text-center px-2">
                        {{ $competitor->name }}
                    </td>
                    <td class="text-center px-2">
                        0
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
