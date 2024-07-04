@extends('layouts.boca')

@section('head')
    <style>
        .gold {
            color: #c3b360;
            font-size: 1.6em;
        }

        .silver {
            color: silver;
            font-size: 1.4em;
        }

        .bronze {
            color: #cd7f32;
            font-size: 1.3em;
        }
    </style>
@endsection


@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Ranking:
            </b>
        </div>
        <div class="col" style="text-align:center;">
            <h1>
                {{ $problem->title }}
            </h1>
        </div>
        <div class="col">
        </div>
    </div>
    <div class="row">
        @foreach ($categories as $category)
            <div class="col-4" style="width: fit-content; !important">
                <h4>{{ $category }}</h4>
                <table>
                    <table border="1">
                        <thead>
                            <tr>
                                <th class="px-1"><b>#</b></th>
                                <th class="text-center"><b>User</b></th>
                                <th class="text-center"><b>Points</b></th>
                                <th class="text-center"><b>Submission</b></th>
                                <th class="text-center"><b>Reference</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($problem->ranks($category)->orderBy('value', 'desc')->limit(10)->with('submitRun.user')->get() as $ranking)
                                <tr>
                                    <td class="pr-2">
                                        #{{ $loop->iteration }}
                                    </td>
                                    <td class="px-2" style="display: flex;align-items: center;">
                                        {{ $ranking->submitRun->user->name }}
                                        <i
                                            class="las la-trophy {{ $loop->iteration == 1 ? 'gold' : ($loop->iteration == 2 ? 'silver' : ($loop->iteration == 3 ? 'bronze' : '')) }}"></i>
                                    </td>
                                    <td class="px-2">
                                        {{ $ranking->value }}
                                    </td>
                                    <td class="px-2 text-center">
                                        {{ $ranking->language }}#{{ $ranking->submit_run_id }}
                                    </td>
                                    <td class="px-2 text-center">
                                        {{ $ranking->reference }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </table>
            </div>
        @endforeach
    </div>
@endsection
