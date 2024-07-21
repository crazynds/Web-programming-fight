@extends('layouts.boca')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Contest: {{ $contest->title }}
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('contest.index') }}">Go Back</a>
        </div>
    </div>
    <div class="row">
        <div class="col-6">
            painel de leaderboard

            <form action="{{ route('contest.recomputateScores', ['contest' => $contest->id]) }}" method="post">
                @csrf
                <button type="submit" style="float:right"> Re-computate Scores </button>
            </form>
        </div>
        <div class="col-6">
            <h4>Clarifications</h4>
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
            @foreach ($contest->clarifications()->orderBy('id', 'desc')->get() as $clarification)
                <form
                    action="{{ route('contest.clarification.update', ['contest' => $contest->id, 'clarification' => $clarification->id]) }}"
                    method="post">
                    @csrf
                    @method('PUT')
                    <div style="border: #bbb solid 1px;border-radius: 3px;padding: 10px;background-color: whitesmoke; margin-left: 15%;font-size: 0.8em;"
                        class="shadow-md mb-1">
                        <b>Question: </b> <small>{{ $clarification->problem->title }}</small><br>
                        {{ $clarification->question }}
                    </div>
                    <textarea name="answer" id="answer" cols="50" rows="4">{{ $clarification->answer ?? '' }}</textarea>

                    <br>
                    <label for="public" class="form-label">Public: </label>
                    <input type='hidden' id="hidden_public" value='0' name='public'>
                    <input type="checkbox" id="public" name="public" value='1'
                        @if ($clarification->public) checked @endif />

                    <button type="submit" style="float:right"> Submit </button>
                </form>
                <form
                    action="{{ route('contest.clarification.destroy', ['contest' => $contest->id, 'clarification' => $clarification->id]) }}"
                    method="post">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="float:right"> Delete </button>
                </form>
                <hr />
            @endforeach
        </div>
    </div>
@endsection

@section('script')
    @if ($contest->start_time->gt(now()))
        <script>
            // Set the date we're counting down to
            var countDownDate = new Date(
                "{{ $contest->start_time->lt(now()) ? $contest->endTime() : $contest->start_time }}"
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
                document.getElementById("clock").innerHTML = displayTime;


                // If the count down is finished, write some text
                if (distance < 0) {
                    document.getElementById("clock").innerHTML = "Starting Contest...";
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
