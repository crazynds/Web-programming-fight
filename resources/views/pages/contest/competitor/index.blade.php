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
                <th class="text-center px-2" style="min-width:200px;"><b>Name</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($competitors as $competitor)
                <tr>
                    <td>
                        {{ $competitor->acronym }}
                    </td>
                    <td class=" px-2">
                        {{ $competitor->name }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
