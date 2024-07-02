@extends('layouts.boca')

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
        <hr />
        <div class="row mathjax">
            {{ Illuminate\Mail\Markdown::parse($contest->description) }}
        </div>
        @if (!$competitor && $contest->start_time->addMinutes($contest->duration)->gt(now()))
            <hr />
            <div class="row">
                <div class="col">
                    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
                        style="justify-content: end;display:flex;"
                        action="{{ route('contest.join', ['contest' => $contest->id]) }}">
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
                        {!! htmlFormButton('Submit', [
                            'style' => 'padding: 0px 20px;margin-bottom: 8px;',
                            'class' => 'btn btn-primary',
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
    @if ($contest->start_time->gt(now()))
        <script>
            // Set the date we're counting down to
            var countDownDate = new Date("{{ $contest->start_time }}").getTime();

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
                    clearInterval(x);
                    document.getElementById("clock").innerHTML = "Entering Contest...";
                    location.reload()
                }
            };

            clock();
            setInterval(clock, 1000);
        </script>
    @endif
@endsection
