@extends('layouts.boca')

@section('head')
    <style>
        .balloon {
            width: 18px;
            height: 32px;

            background-size: contain;
            background-repeat: no-repeat;
            -webkit-mask-image: url('{{ asset('img/ballon.png') }}');

            -webkit-mask-size: contain;
            -webkit-mask-repeat: no-repeat;
            mask-image: url('{{ asset('img/ballon.png') }}');

            mask-size: contain;
            mask-repeat: no-repeat;
            position: absolute;
            z-index: 1;
        }

        .balloon-shadow {
            content: '';
            position: absolute;
            top: -1px;
            left: -1px;
            width: 23px;
            height: 35px;
            background-color: black;
            -webkit-mask-image: url('{{ asset('img/ballon.png') }}');
            -webkit-mask-size: contain;
            -webkit-mask-repeat: no-repeat;
            mask-image: url('{{ asset('img/ballon.png') }}');
            mask-size: contain;
            mask-repeat: no-repeat;
            z-index: -1;
        }

        .balloon-A {
            background-color: #FFFFFF;
        }

        /* Branco */
        .balloon-B {
            background-color: #000000;
        }

        /* Preto */
        .balloon-C {
            background-color: #FF0000;
        }

        /* Vermelho */
        .balloon-D {
            background-color: #FF7F00;
        }

        /* Laranja */
        .balloon-E {
            background-color: #FFFF00;
        }

        /* Amarelo */
        .balloon-F {
            background-color: #00FF00;
        }

        /* Verde */
        .balloon-G {
            background-color: #0000FF;
        }

        /* Azul */
        .balloon-H {
            background-color: #4B0082;
        }

        /* Anil */
        .balloon-I {
            background-color: #8B00FF;
        }

        /* Violeta */
        .balloon-J {
            background-color: #808080;
        }

        /* Cinza */
        .balloon-K {
            background-color: #FF1493;
        }

        /* Rosa profundo */
        .balloon-L {
            background-color: #00FFFF;
        }

        /* Turquesa escuro */
        .balloon-M {
            background-color: #FFD700;
        }

        /* Dourado */
        .balloon-N {
            background-color: #8A2BE2;
        }

        /* Azul violeta */
        .balloon-O {
            background-color: #DC143C;
        }

        /* Carmesim */
        .balloon-P {
            background-color: #00FA9A;
        }

        /* Verde primavera */
        .balloon-Q {
            background-color: #FF4500;
        }

        /* Laranja avermelhado */
        .balloon-R {
            background-color: #2E8B57;
        }

        /* Verde marinho */
        .balloon-S {
            background-color: #4682B4;
        }

        /* Azul aço */
        .balloon-T {
            background-color: #D2691E;
        }

        /* Chocolate */
        .balloon-U {
            background-color: #DA70D6;
        }

        /* Orquídea */
    </style>
@endsection
@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Leaderboard:
            </b>
        </div>
        <div class="col">
            @if ($blind)
                <h3>Blind Time!!</h3>
            @endif
        </div>
    </div>

    <table border="1"@if ($blind) style="background-color: #0002" @endif>
        <thead>
            <tr>
                <th class="px-1"><b>#</b></th>
                <th class="text-center px-2" style="min-width:200px;"><b>Name</b></th>
                @php($letter = 'A')
                @foreach ($problems as $problem)
                    <th class="text-center px-2 h6"><b>{{ $letter++ }}</b></th>
                @endforeach
                <th class="text-center px-2"><b>Total</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($competitors as $competitor)
                <tr>
                    <td>
                        {{ $competitor->acronym }}
                    </td>
                    <td class="px-2">
                        {{ $competitor->name }}
                    </td>
                    @php($letter = 'A')
                    @php($questions = 0)
                    @foreach ($problems as $problem)
                        <td class="px-2">
                            @if (isset($competitor->scores[$problem]))
                                @php($questions++)
                                @php($score = $competitor->scores[$problem])
                                <span class="d-flex">
                                    <div style="position:relative; width: 20px; height: 32px">
                                        <div class="balloon balloon-{{ $letter++ }}"></div>
                                        <div class="balloon-shadow"></div>
                                    </div>
                                    <span style="padding-top: 14px;font-size: smaller">
                                        @if ($contest->time_based_points || $contest->parcial_solution)
                                            {{ $score->score }}
                                            ({{ $competitor->__get('sum_submissions_' . $problem) }})
                                        @else
                                            {{ $competitor->__get('sum_submissions_' . $problem) }} /
                                            {{ $score->penality }}
                                        @endif
                                    </span>
                                </span>
                            @elseif ($competitor->__get('sum_submissions_' . $problem) > 0)
                                <span>
                                    -- ({{ $competitor->__get('sum_submissions_' . $problem) }})
                                </span>
                            @endif
                        </td>
                    @endforeach

                    <td class="text-center px-2">
                        @if ($contest->time_based_points || $contest->parcial_solution)
                            {{ $competitor->scores_sum_score }} ({{ $questions }})
                        @else
                            {{ $competitor->scores_sum_score }} ({{ $competitor->scores_sum_penality }})
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
