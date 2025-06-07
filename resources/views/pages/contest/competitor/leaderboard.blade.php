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

        #tr {
            transition: all 1s ease;
        }
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

    <table border="1"@if ($blind) style="background-color: #0002" @endif id="ranking">
        <thead>
            <tr>
                <th class="px-1">#</th>
                @if (!$contest->individual)
                    <th></th>
                @endif
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
                    <td class="px-1">
                        {{ $loop->iteration }}⁰
                    </td>
                    @if (!$contest->individual)
                        <td class="px-1">
                            {{ $competitor->team->institution_acronym ?? '' }}
                        </td>
                    @endif
                    <td class="px-2">
                        {{ $competitor->fullName() }}
                    </td>
                    @php($letter = 'A')
                    @php($questions = 0)
                    @foreach ($problems as $problem)
                        <td class="px-2">
                            @php($questions++)
                            @php($score = $competitor->scores[$problem] ?? null)

                            <span class="d-flex @if (isset($competitor->scores[$problem])) scored @endif"
                                id="score-{{ $competitor->id }}-{{ $problem }}"
                                data-attempts="{{ $competitor->__get('sum_submissions_' . $problem) }}"
                                data-penality="{{ $score->penality ?? 0 }}">
                                @if ($score)
                                    <div style="position:relative; width: 20px; height: 32px">
                                        <div class="balloon balloon-{{ $letter }}"></div>
                                        <div class="balloon-shadow"></div>
                                    </div>
                                    <span style="padding-top: 14px;font-size: smaller">
                                        {{ $competitor->__get('sum_submissions_' . $problem) . '/' . floor(abs($score->submission->created_at->diffInMinutes($contest->start_time))) }}
                                    </span>
                                @elseif ($competitor->__get('sum_submissions_' . $problem) > 0)
                                    -- ({{ $competitor->__get('sum_submissions_' . $problem) }})
                                @endif
                            </span>
                        </td>
                        @php($letter++)
                    @endforeach

                    <td class="text-center" style="padding-left: 10px; padding-right: 4px">
                        <span id="score">
                            {{ $competitor->scores_sum_score ?? 0 }}
                        </span>

                        @if ($contest->time_based_points || $contest->parcial_solution)
                            (<small id="questions">{{ $questions ?? 0 }}</small>)
                        @else
                            (<small id="penality">{{ $competitor->scores_sum_penality ?? 0 }}</small>)
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if (config('app.livewire'))
        @php($global = true)
        @php($lastUpdated = \Illuminate\Support\Carbon::now())
        <livewire:sync-submission-component :global="$global" :contest="$contest" :lastCheck="$lastUpdated" />
    @endif
@endsection

@section('script')
    <script type='module'>
        const channel = '{{ $channel }}'

        function confeti(el = null) {

            var duration = 10 * 1000;
            var animationEnd = Date.now() + duration;
            var defaults = {
                startVelocity: 10,
                spread: 60,
                ticks: 45,
                zIndex: 0
            };
            var lastTime = Date.now();

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }
            var interval = setInterval(function() {
                var timeLeft = animationEnd - Date.now();
                if (document.hidden) {
                    animationEnd += Date.now() - lastTime;
                    lastTime = Date.now();
                    return;
                }
                lastTime = Date.now();

                if (timeLeft <= 0) {
                    $(el).removeClass('blink')
                    return clearInterval(interval);
                } else {
                    $(el).addClass('blink')
                }
                if (el) {
                    const rect = el.getBoundingClientRect();
                    defaults.origin = {
                        x: (rect.left + rect.width / 2) / window.innerWidth,
                        y: (rect.top + rect.height / 2) / window.innerHeight
                    }
                }
                var particleCount = 10 * (timeLeft / duration) * randomInRange(0.6, 1.2) + 1;
                // since particles fall down, start a bit higher than random
                window.confetti({
                    origin: {
                        x: randomInRange(0.1, 0.3),
                        y: Math.random() - 0.2
                    },
                    ...defaults,
                    particleCount,
                });
                window.confetti({
                    origin: {
                        x: randomInRange(0.7, 0.9),
                        y: Math.random() - 0.2
                    },
                    ...defaults,
                    particleCount,
                });
            }, 500);
        }
        const problemNumber = {
            @php($letter = 'A')
            @foreach ($problems as $problem)
                '{{ $problem }}': '{{ $letter++ }}',
            @endforeach
        }
        const fix = {
            'a': 2
        }
        const updateRow = function(row, data) {
            row.text('⏱︎');
            if (data.status != 'Judged' && data.status != 'Error')
                row.addClass('notJudged blink');
            else {
                row.removeClass('notJudged blink');
                const attempts = Number(row.data('attempts'));
                switch (data.result) {
                    case 'Accepted':
                        const ballon = problemNumber[data.problem.id]
                        const submissao = new Date(data.full_datetime.replace(' ', 'T'));
                        const inicio = new Date('{{ $contest->start_time }}');
                        const diffMs = submissao - inicio;
                        const diffMin = Math.floor(diffMs / 1000 / 60);
                        const ballonHtml =
                            '<div style="position:relative; width: 20px; height: 32px"><div class="balloon balloon-' +
                            ballon + '"></div><div class="balloon-shadow"></div></div>' +
                            '<span style="padding-top: 14px;font-size: smaller">' +
                            (attempts + 1) + '/' + diffMin +
                            '</span>'

                        row.html(ballonHtml);


                        row.data('penality', attempts * {{ $contest->penality }} + diffMin);
                        break;
                    default:
                        row.data('attempts', attempts + 1);
                        row.attr('data-attempts', attempts + 1);
                        row.text('-- (' + (attempts + 1) + ')');

                }
            }

        }

        function orderRanking() {
            const $rows = $('#ranking tbody tr');
            const sorted = $rows.toArray().sort((a, b) => {
                const $a = $(a),
                    $b = $(b);
                const scoreA = parseInt($a.find('#score').text(), 10);
                const scoreB = parseInt($b.find('#score').text(), 10);


                @if ($contest->time_based_points || $contest->parcial_solution)
                    const penA = -parseInt($a.find('#questions').text(), 10);
                    const penB = -parseInt($b.find('#questions').text(), 10);
                @else
                    const penA = parseInt($a.find('#penality').text(), 10);
                    const penB = parseInt($b.find('#penality').text(), 10);
                @endif

                if (scoreB !== scoreA) return scoreB - scoreA;
                return penA - penB;
            });
            // Aplica a nova ordem com animação
            const $tbody = $('#ranking tbody');
            $tbody.empty();
            sorted.forEach(row => $tbody.append(row));
        }
        const updateLeaderboard = function(competitor_id) {
            const count = $('#row' + competitor_id + ' .scored').length;
            @if ($contest->time_based_points || $contest->parcial_solution)
                // caso em que a pontuação é baseada em tempo ou pode ser solução parcial
                $('#row' + competitor_id + ' #questions').text(count);
            @else
                $('#row' + competitor_id + ' #score').text(count);
                var tot = 0
                Object.keys(problemNumber).forEach((key) => {
                    const num = $('#score-' + competitor_id + '-' + key).data('penality');
                    tot += Number(num);
                })
                $('#row' + competitor_id + ' #penality').text(tot);
            @endif
            setTimeout(orderRanking, 100);
        }
        window.updateSubmission = function(data) {
            if (!data.contest) return;
            var row = $('#score-' + data.contest.competitor_id + '-' + data.problem.id);
            if (row.length == 0 || row.hasClass('scored2')) return;
            if (data.status != 'Judged' && data.status != 'Error') {
                updateRow(row, data);
            } else {
                var suspense = false
                row.removeClass('blink')
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
                            row.addClass('scored');
                            confeti(row[0]);
                            break;
                    }
                    updateLeaderboard(data.contest.competitor_id)
                }
                setTimeout(geraResultado, 500)
            }
        }
        @if (!config('app.livewire'))
            window.addEventListener("load", function() {
                window.Echo.private(channel)
                    .listen('NewSubmissionEvent', (data) => {
                        window.updateSubmission(data.data)
                    })

                window.Echo.private(channel)
                    .listen('UpdateSubmissionEvent', (data) => {
                        window.updateSubmission(data.data)
                    });
            })
        @endif
        @if ($blind && $contest->endTime()->lt(now()))
            // Set the date we're counting down to
            var countDownDate = new Date("{{ $contest->endTimeWithExtra()->toIso8601String() }}").getTime();

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
        @endif
    </script>
@endsection
