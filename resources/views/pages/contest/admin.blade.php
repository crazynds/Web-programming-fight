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
        <div class="col-4" style="border-right: black dashed 1px; min-height: 70vh">
            <form action="{{ route('contest.recomputateScores', ['contest' => $contest->id]) }}" method="post">
                @csrf
                <button type="submit" style="float:right"> Re-computate Scores </button>
            </form>
            <form action="{{ route('contest.settings', ['contest' => $contest->id]) }}" method="POST">
                <button type="submit">
                    Save Settings
                </button>
                <div class="mt-2">
                    <h5>Problem Auto-Judge Settings</h5>
                </div>
                @csrf
                @method('PUT')
                <div>
                    @foreach($contest->problems as $index => $problem)
                        @php
                            $letter = chr(65 + $index); // Convert index to letter (0=A, 1=B, etc.)
                        @endphp
                            <label class="form-check-label fw-bold" for="problem-{{ $problem->id }}">
                                <div style="border: gray solid 1px; display:inline-block; padding: 5px; margin: 2px;">
                                    {{ $letter }}
                                    <br>
                                    <input type="hidden" name="auto_judge[{{ $problem->id }}]" value="0">
                                    <input class="form-check-input me-2" 
                                            type="checkbox" 
                                            id="problem-{{ $problem->id }}" 
                                            name="auto_judge[{{ $problem->id }}]" 
                                            value="1"
                                            @if($problem->pivot->auto_judge) checked @endif
                                            onclick="this.previousSibling.value = this.checked ? '1' : '0'">
                                </div>
                            </label>
                    @endforeach
                </div>
            </form>
            <div class="mt-4">
                <h5>Pending Submissions</h5>
                @foreach ($contest->submissions()->with(['problem', 'competitor'])->where('status', App\Enums\SubmitStatus::AwaitingAdminJudge)->orderBy('id')->get() as $submission)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    
                                    <h6 class="card-title mb-1">
                                        <a href="#" onclick="openModal({{ $submission->id }})">
                                            #{{ $submission->id }}
                                        </a>
                                        {{ $submission->problem->title }} 
                                        <br>
                                        <small class="text-muted">{{ $submission->competitor->fullName() }}</small>
                                    </h6>
                                    <p class="card-text small text-muted mb-0">
                                        Submission #{{ $submission->id }} • {{ $submission->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <form action="{{ route('contest.submission.accept',['contest' => $contest->id, 'submission' => $submission->id]) }}" method="post" class="d-inline-block">
                                    @csrf
                                    <button type="submit" title="Accept">
                                        Accept
                                    </button>
                                </form>
                                <form action="{{ route('contest.submission.rejectAI',['contest' => $contest->id, 'submission' => $submission->id]) }}" method="post" class="d-inline-block">
                                    @csrf
                                    <button type="submit" title="Reject AI">
                                        Reject AI
                                    </button>
                                </form>
                                <form action="{{ route('contest.submission.rejectWA',['contest' => $contest->id, 'submission' => $submission->id]) }}" method="post" class="d-inline-block">
                                    @csrf
                                    <button type="submit" title="Reject WA">
                                        Reject WA
                                    </button>
                                </form>
                                <form action="{{ route('contest.submission.rejectTL',['contest' => $contest->id, 'submission' => $submission->id]) }}" method="post" class="d-inline-block">
                                    @csrf
                                    <button type="submit" title="Reject TL">
                                        Reject TL
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-4" style="border-right: black dashed 1px; min-height: 70vh">
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
                    method="post"
                    @if(!$clarification->answer)
                    style="background: lightblue"
                    @endif
                    >
                    @csrf
                    @method('PUT')
                    <div style="border: #bbb solid 1px;border-radius: 3px;padding: 10px;background-color: whitesmoke; margin-left: 15%;font-size: 0.8em;"
                        class="shadow-md mb-1">
                        <b>{{ $clarification->competitor->acronym }}: </b>
                        <small>{{ $clarification->problem->title }}</small><br>
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
                    <button type="submit" style="float:right;margin-right: 80px;margin-top: -34px;"> Delete </button>
                </form>
                <hr />
            @endforeach
        </div>
        <div class="col-4">
            <table border="1" id="ranking">
                <thead>
                    <tr>
                        <th class="px-1">#</th>
                        <th class="text-center px-2"><b>Actions</b></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contest->competitors as $competitor)
                    <tr>
                        <td class="px-2">
                            {{ $competitor->fullName() }}
                        </td>
                        <td>

                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal fade codeModal" id="codeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document" style="max-width:80%">
            <div class="modal-content" style="padding: 10px;">
                <div style="margin-bottom: 4px">
                    <button style="float:right" type="button" class="copy" aria-label="copy" onclick="copyCode()">
                        Copy
                    </button>
                </div>
                <pre id="code" style="border: 1px black solid;padding: 4px">Code...</pre>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    window.copyCode = function() {
        var range = document.createRange();
        range.selectNode(document.getElementById("code"));
        window.getSelection().removeAllRanges(); // clear current selection
        window.getSelection().addRange(range); // to select text
        document.execCommand("copy");
    }
    window.openModal = function(id) {
        var url = '{{ route('api.submission.code', ['submission' => -1]) }}'.replace('-1', id)
        $('#codeModal').modal("show")
        $('#codeModal').find('#code').html(`
                <div class="d-flex justify-content-center">
                    <div class="spinner-grow" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `)
        $.get(url, function(data) {
            if (data.code)
                $('#codeModal').find('#code').html(hljs.highlight(
                    data.code,
                    { language: data.language }
                ).value)
        });
    }
</script>
@if ($contest->start_time->gt(now()))
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
