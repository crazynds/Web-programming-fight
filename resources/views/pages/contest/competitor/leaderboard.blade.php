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
                <h3>Blind Time!! <span id="clock"></span></h3>
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
                <tr id="row{{ $competitor->id }}" data-id="{{ $competitor->id }}">
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
                            <span class="d-flex" id="score-{{ $competitor->id }}-{{ $problem }}">
                                @if (isset($competitor->scores[$problem]))
                                    @php($questions++)
                                    @php($score = $competitor->scores[$problem])
                                    <div style="position:relative; width: 20px; height: 32px">
                                        <div class="balloon balloon-{{ $letter }}"></div>
                                        <div class="balloon-shadow"></div>
                                    </div>
                                    <span style="padding-top: 14px;font-size: smaller">
                                        @if ($contest->time_based_points || $contest->parcial_solution)
                                            {{ $score->score }}({{ $competitor->__get('sum_submissions_' . $problem) }})
                                        @else
                                            {{ $competitor->__get('sum_submissions_' . $problem) . '/' . $score->penality }}
                                        @endif
                                    </span>
                                @elseif ($competitor->__get('sum_submissions_' . $problem) > 0)
                                    -- ({{ $competitor->__get('sum_submissions_' . $problem) }})
                                @endif
                            </span>
                        </td>
                        @php($letter++)
                    @endforeach

                    <td class="text-center" style="padding-left: 10px; padding-right: 4px">
                        @if ($contest->time_based_points || $contest->parcial_solution)
                            {{ $competitor->scores_sum_score ?? 0 }} <small>({{ $questions ?? 0 }})</small>
                        @else
                            {{ $competitor->scores_sum_score ?? 0 }}
                            <small>({{ $competitor->scores_sum_penality ?? 0 }})</small>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('script')
    <script>
        const channel = '{{ $channel }}'

        function confeti() {
            var duration = 10 * 1000;
            var animationEnd = Date.now() + duration;
            var defaults = {
                startVelocity: 30,
                spread: 360,
                ticks: 60,
                zIndex: 0
            };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }
            setTimeout(function() {
                var interval = setInterval(function() {
                    var timeLeft = animationEnd - Date.now();

                    if (timeLeft <= 0) {
                        return clearInterval(interval);
                    }

                    var particleCount = 50 * (timeLeft / duration);
                    // since particles fall down, start a bit higher than random
                    window.confetti({
                        ...defaults,
                        particleCount,
                        origin: {
                            x: randomInRange(0.1, 0.3),
                            y: Math.random() - 0.2
                        }
                    });
                    window.confetti({
                        ...defaults,
                        particleCount,
                        origin: {
                            x: randomInRange(0.7, 0.9),
                            y: Math.random() - 0.2
                        }
                    });
                }, 250);
            }, 500)
            var count = 200;
            const defaults2 = {
                origin: {
                    y: 0.9
                }
            };

            function fire(particleRatio, opts) {
                confetti({
                    ...defaults2,
                    ...opts,
                    particleCount: Math.floor(count * particleRatio)
                });
            }

            fire(0.25, {
                spread: 26,
                startVelocity: 55,
            });
            fire(0.2, {
                spread: 60,
            });
            fire(0.35, {
                spread: 100,
                decay: 0.91,
                scalar: 0.8
            });
            fire(0.1, {
                spread: 120,
                startVelocity: 25,
                decay: 0.92,
                scalar: 1.2
            });
            fire(0.1, {
                spread: 120,
                startVelocity: 45,
            });
        }
        const updateRow = function(row, data) {
            row.css('display', 'table-row');
            row.attr('id', 'row' + data.id);
            if (data.status != 'Judged' && data.status != 'Error')
                row.addClass('notJudged blink');
            else
                row.removeClass('notJudged blink');
        }
        window.addEventListener("load", function() {
            window.Echo.private(channel)
                .listen('NewSubmissionEvent', (data) => {
                    /*data = data.data
                    var row = $('#row' + data.contest.competitor_id);
                    if (row.length != 0) {
                        updateRow(row, data);
                    } */
                })

            window.Echo.private(channel)
                .listen('UpdateSubmissionEvent', (data) => {
                    data = data.data
                    switch (data.result) {
                        case 'Compilation error':
                        case 'Runtime error':
                        case 'Error':
                        case 'Wrong answer':
                        case 'Time limit':
                        case 'Memory limit':
                        case 'Accepted':
                            location.reload();
                            break;
                    }
                    /*
                    var row = $('#row' + data.contest.competitor_id);
                    if (row.length == 0) {
                        if (userId != null && userId != data.user_id) return
                        row = $('#template-row').clone();
                        updateRow(row, data);
                        $('#table-body').prepend(row);
                        return;
                    }
                    if (data.status != 'Judged' && data.status != 'Error') {
                        updateRow(row, data);
                    } else {
                        row.removeClass('blink')
                        row.find('#status').text(data.status);
                        switch (data.result) {
                            case 'Wrong answer':
                            case 'Time limit':
                            case 'Memory limit':
                            case 'Runtime error':
                            case 'Accepted':
                                suspense = data.suspense;
                                break;
                            case 'Compilation error':
                            case 'Error':
                            default:
                                failed()
                                suspense = false;
                                break;
                        }

                        const geraResultado = function() {
                            updateRow(row, data);
                            switch (data.result) {
                                case 'Compilation error':
                                case 'Runtime error':
                                case 'Error':
                                case 'Wrong answer':
                                case 'Time limit':
                                case 'Memory limit':
                                default:
                                    break;
                                case 'Accepted':
                                    confeti();
                                    break;
                            }
                        }
                        geraResultado();
                    }*/
                });
        })

        function reiniciarPagina() {
            location.reload();
        }

        // Definir um temporizador para reiniciar a página a cada 5 minutos
        setTimeout(reiniciarPagina, 1000 * 60 * 5);
    </script>
    @if ($blind && $contest->endTime()->lt(now()))
        <script>
            // Set the date we're counting down to
            var countDownDate = new Date("{{ $contest->endTimeWithExtra() }}").getTime();

            const clock = function() {

                // Get today's date and time
                var now = new Date().getTime();

                // Find the distance between now and the count down date
                var distance = countDownDate - now;

                // Time calculations for days, hours, minutes and seconds
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Display the result in the element with id="clock"
                let displayTime = "";

                if (days > 0) {
                    displayTime += days.toString().padStart(2, '0') + "d ";
                    displayTime += hours.toString().padStart(2, '0') + ":";
                    displayTime += minutes.toString().padStart(2, '0') + ":";
                    displayTime += seconds.toString().padStart(2, '0');
                } else if (hours > 0) {
                    displayTime += hours.toString().padStart(2, '0') + ":";
                    displayTime += minutes.toString().padStart(2, '0') + ":";
                    displayTime += seconds.toString().padStart(2, '0');
                } else {
                    displayTime += minutes.toString().padStart(2, '0') + ":";
                    displayTime += seconds.toString().padStart(2, '0');
                }
                document.getElementById("clock").innerHTML = displayTime;


                // If the count down is finished, write some text
                if (distance < 0) {
                    document.getElementById("clock").innerHTML = "...";
                    location.reload()
                    clearInterval(x);
                } else if (minutes == 10 && seconds == 0) {
                    location.reload()
                }
            };

            clock();
            var x = setInterval(clock, 1000);
        </script>
    @endif
@endsection
