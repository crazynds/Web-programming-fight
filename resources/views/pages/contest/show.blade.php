@extends('layouts.base')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Contest:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('contest.index') }}">Go Back</a>
        </div>
    </div>
    <div style="border: #bbb solid 1px;border-radius: 3px;padding: 10px;background-color: whitesmoke;" class="shadow-lg">
        <div class="row" style="position:relative">
            <h1 class="text-center mb-0">
                <strong>
                    {{ $contest->title }}
                </strong>
                <span style="position: absolute; left: 10px" id="clock">
                </span>
            </h1>
            <div class="hstack gap-2 justify-content-center">
                <div>
                    #{{ $contest->id }}
                </div>
                <div class="vr"></div>
                <small>
                    Made by: <strong>{{ $contest->user->name }}</strong>
                </small>
                <div class="vr"></div>
                <div>
                    {{ $contest->problems->count() }} Problemas
                </div>
            </div>
            <div class="hstack gap-2 justify-content-center">
            </div>
        </div>
        @if (
            $competitor &&
                $contest->start_time->subMinutes(10)->lt(now()) &&
                $contest->start_time->addMinutes($contest->duration)->gt(now()))
            <hr />
            <form class="d-flex justify-content-center" method="post"
                action="{{ route('contest.enter', ['contest' => $contest->id]) }}">
                @csrf
                <b>
                    > > > > > > > > > > > >
                    <button type="submit" style="font-weight: 600;width: 200px;">Enter in contest</button>
                    < < < < < < < < < < < < </b>
            </form>
        @endif

        <hr />
        <div class="row mathjax">
            <div class="col">
                {{ Illuminate\Mail\Markdown::parse($contest->description) }}
            </div>
        </div>
        <hr />

        <div class="row">
            <div class="col">
                <ul>
                    @if ($contest->parcial_solution)
                        <li style="cursor: help;text-decoration: underline;"
                            title="You only start earning points if you get at least 30% of the test cases right, with a maximum score of 60% if there is complete acceptance for all cases except one.">
                            Parcial solution score parcial points.</li>
                    @else
                        <li>Only solutions that pass all test cases score.</li>
                    @endif
                    @if ($contest->show_wrong_answer)
                        <li style="cursor: help;text-decoration: underline;"
                            title="Show difference output in Wrong Answer between the correct output and your solution output.">
                            Wrong answer runs will show the difference in the output.</li>
                    @else
                    @endif
                    @if ($contest->individual)
                        <li>Indivual participation only.</li>
                    @else
                        <li>Team participation onlty.</li>
                    @endif
                    @if ($contest->time_based_points)
                        <li style="cursor: help;text-decoration: underline;"
                            title="Over time, the points for each question will decrease from 100% of the points at the beginning of the contest to 70% of the points at the end.">
                            Points are based on time.</li>
                        <li style="cursor: help;text-decoration: underline;"
                            title="Penality decrease the amount of points recived.">
                            Penality increase only on non accepted submissions.
                        </li>
                    @else
                        <li style="cursor: help;text-decoration: underline;"
                            title="Penality are used to break a tie if it happens.">
                            Penality increase with time and with non accepted submissions.
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        @if (!$competitor && $contest->start_time->addMinutes($contest->duration)->gt(now()))
            <hr />
            <div class="row">
                <div class="col">
                    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
                        style="justify-content: end;display:flex;"
                        action="{{ route('contest.register', ['contest' => $contest->id]) }}">
                        @csrf

                        @if ($contest->is_private)
                            <input style="margin-right:10px; max-width: 300px" type="text" class="form-control"
                                name="password" placeholder="Password" />
                        @endif

                        @if (!$contest->individual)
                            <select name="team" style="margin-right:10px" placeholder="Select a Team">
                                <option value="" disabled selected>Select a Team</option>
                                @foreach (Auth::user()->myTeams as $team)
                                    <option value="{{ $team->id }}">
                                        #{{ $team->acronym }} - {{ $team->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        {!! htmlFormButton('Register', [
                            'style' => 'padding: 0px 20px;margin-bottom: 8px;',
                        ]) !!}
                    </form>
                </div>
            </div>
        @elseif($competitor && $contest->start_time->addMinutes($contest->duration)->gt(now()))
            <hr />
            <div class="row">
                <div class="col">
                    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
                        style="justify-content: end;display:flex;"
                        action="{{ route('contest.unregister', ['contest' => $contest->id]) }}">
                        @csrf
                        {!! htmlFormButton('Cancel Registration', [
                            'style' => 'padding: 0px 20px;margin-bottom: 8px;',
                        ]) !!}
                    </form>
                </div>
            </div>
        @endif
    </div>
    @if ($competitor)
        <div class="row mt-3">
            <div class="alert alert-success">
                <ul>
                    <li>You are participating in this contest!</li>
                    <li>Your name will be: {{ $competitor->fullName() }}</li>
                </ul>
            </div>
        </div>
    @endif
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
@endsection

@section('script')
    @if ($contest->endTime()->gt(now()))
        <script type='module'>
            // Set the date we're counting down to
            var countDownDate = new Date(
                "{{ $contest->start_time->lt(now()) ? $contest->endTime()->toIso8601String() : $contest->start_time->toIso8601String() }}"
            ).getTime();

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
                document.getElementById("clock").innerHTML =
                    @if (!$contest->start_time->lt(now()))
                        "Starting in<br>"
                    @else
                        "Ending in<br>"
                    @endif + displayTime;


                // If the count down is finished, write some text
                if (distance < 0) {
                    document.getElementById("clock").innerHTML =
                        @if (!$contest->start_time->lt(now()))
                            "Starting Contest..."
                        @else
                            "Ending Contest..."
                        @endif ;
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
