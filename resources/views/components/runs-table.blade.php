<div>
    <table border="1">
        <thead>
            <tr>
                <th class="px-2"><b>#</b></th>
                <th class="text-center"><b>When</b></th>
                <th class="text-center"><b>Who</b></th>
                <th class="text-center"><b>Problem</b></th>
                <th class="text-center"><b>Lang</b></th>
                <th class="text-center"><b>Status</b></th>
                <th class="text-center"><b>Result</b></th>
                <th class="text-center"><b>Cases</b></th>
                <th class="text-center"><b>Resources</b></th>
                <th class="text-center"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody id="table-body">
            <tr id="template-row" style="display: none;">
                <td id="id">
                    #id
                </td>
                <td class="px-2 text-center">
                    <small id="datetime">
                        H:i:s
                    </small>
                </td>
                <td class="px-2" id="user">
                    username
                </td>
                <td class="px-2">
                    <a href="" id="title">
                        title
                    </a>
                </td>
                <td class="px-2" id="lang">
                    lang
                </td>
                <td class="px-2">
                    <strong id="status">
                        status
                    </strong>
                </td>
                <td class="px-2">
                    <span id="result">
                        Result
                    </span>
                </td>
                <td class="px-2 text-center">
                    <small id="testCases">
                        cases
                    </small>
                </td>
                <td class="px-2" style="font-size: 0.9em" id="resources">
                    resources
                </td>
                <td class="px-2">
                    <div class="hstack gap-1">
                    </div>
                </td>
            </tr>
            @php
                $lastUpdated = \Illuminate\Support\Carbon::now()->subHour();
                $contest ??= ($contestService->contest ?? null);
            @endphp
            @foreach ($submissions as $submission)
                @php
                    $lastUpdated = max($submission->updated_at, $lastUpdated);
                @endphp
                <tr id="row{{ $submission->id }}" data-id="{{ $submission->id }}"
                    @if ($submission->status != 'Judged' && $submission->status != 'Error') class="notJudged blink" @endif>
                    <td>
                        @can('view', $submission)
                            <a href="#row{{ $submission->id }}" onclick="openModal({{ $submission->id }})">
                                #{{ $submission->id }}
                            </a>
                        @else
                            #{{ $submission->id }}
                        @endcan
                    </td>
                    <td class="px-2 text-center">
                        <small>
                            @if (\Carbon\Carbon::parse($submission->created_at)->format('d/m/Y') != (new DateTime())->format('d/m/Y'))
                                {{ \Carbon\Carbon::parse($submission->created_at)->format('d/m/Y') }}
                            @else
                                {{ \Carbon\Carbon::parse($submission->created_at)->format('H:i:s') }}
                            @endif
                        </small>
                    </td>
                    <td class="px-2">
                        @if ($submission->contest_id && $submission->competitor)
                            @php
                                $nickName = $submission->competitor->fullName();
                            @endphp
                        @else
                            @php
                                $nickName = $submission->user->name;
                            @endphp
                        @endif
                        {{ Str::limit($nickName, 20) }}
                    </td>
                    <td class="px-2">
                        <a href="{{ route('problem.show', ['problem' => $submission->problem->id], false) }}">
                            {{ Str::limit($submission->problem->title, 30) }}
                        </a>
                    </td>
                    <td class="px-2" id="lang">
                        {{ $submission->language }}
                    </td>
                    @if($submission->status == App\Enums\SubmitStatus::getDescription(App\Enums\SubmitStatus::AwaitingAdminJudge))
                        <td class="px-2">
                            <strong id="status">
                                Judging
                            </strong>
                        </td>
                        <td class="px-2">
                            <span id="result"
                                style="color:grey">
                                No result
                            </span>
                        </td>
                        <td class="px-2 text-center">
                            <small id="testCases"
                                style="color:grey">
                                ---
                            </small>
                        </td>
                    @else
                        <td class="px-2">
                            <strong id="status">
                                {{ $submission->status }}
                            </strong>
                        </td>
                        <td class="px-2">
                            <span id="result"
                                @switch($submission->result)
                                @case('Accepted')
                                    style="color:#0a0"
                                    @break
                                @case('Error')
                                @case('File too large')
                                @case('Invalid utf8 file')
                                    style="color:#f00"
                                    @break
                                @case('Wrong answer')
                                    style="color:#a00"
                                    @break
                                @case('Compilation error')
                                @case('Runtime error')
                                    style="color:#aa0"
                                    @break
                                @case('Time limit')
                                @case('Memory limit')
                                @case('Ai detected')
                                    style="color:#00a"
                                    @break
                                @default
                                    style="color:grey"
                                @endswitch>
                                {{ $submission->result }}
                            </span>
                        </td>
                        <td class="px-2 text-center">
                            <small id="testCases"
                                @switch($submission->result)
                                @case('Accepted')
                                    style="color:#0a0"
                                    @break
                                @case('Error')
                                @case('File too large')
                                @case('Invalid utf8 file')
                                    style="color:#f00"
                                    @break
                                @case('Wrong answer')
                                    style="color:#a00"
                                    @break
                                @case('Compilation error')
                                @case('Runtime error')
                                    style="color:#aa0"
                                    @break
                                @case('Time limit')
                                @case('Memory limit')
                                @case('Ai detected')
                                    style="color:#00a"
                                    @break
                                @default
                                    style="color:grey"
                            @endswitch>
                                @switch($submission->result)
                                    @case('Accepted')
                                        All
                                    @break

                                    @case('Wrong answer')
                                        {{ $submission->num_test_cases + 1 }}
                                    @break

                                    @case('Runtime error')
                                    @case('Time limit')

                                    @case('Memory limit')
                                        {{ $submission->num_test_cases + 1 }}
                                    @break
                                    @default
                                        ---
                                    @break
                                @endswitch
                            </small>
                        </td>
                    @endif
                    <td class="px-2" style="font-size: 0.9em" id="resources">
                        @if (isset($submission->execution_time) && $submission->status == 'Judged')
                            {{ number_format($submission->execution_time / 1000, 2, '.', ',') }}s
                        @else
                            --
                        @endif
                        |
                        @if (isset($submission->execution_memory) && $submission->status == 'Judged')
                            {{ $submission->execution_memory }} MB
                        @else
                            --
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @if ($submission->status == 'Judged' || $submission->status == 'Error')
                                @can('update', $submission)
                                    @if ($limit && $submission->status != 'Compilation error')
                                        <a href="{{ route('api.submission.rejudge', ['submission' => $submission->id], false) }}"
                                            class="d-flex action-btn single-silent-click">
                                            <i class="las la-redo-alt"></i>
                                        </a>
                                    @endif
                                @endcan
                                @can('viewOutput', $submission)
                                    @if (isset($submission->output))
                                        <a href="{{ route('submission.show', ['submission' => $submission->id], false) }}"
                                            class="d-flex action-btn">
                                            <i class="las la-poll-h"></i>
                                        </a>
                                    @endif
                                @endcan
                            @endif
                            @can('view', $submission)
                                @if ($submission->file_id != null && !$contestService->inContest)
                                    <a target="_blank"
                                        href="{{ route('submission.download', ['submission' => $submission->id], false) }}"
                                        class="d-flex action-btn">
                                        <i class="las la-file-download"></i>
                                    </a>
                                @endif
                            @endcan
                            @if($contest && $submission->status == 'Judged')
                                @can('admin',$contest)
                                    @if($submission->result == 'Ai detected')
                                        <form action="{{ route('contest.submission.accept',['contest' => $contest->id, 'submission' => $submission->id]) }}" method="post" class="d-inline-block">
                                            @csrf
                                            <button type="submit" class="d-flex action-btn" style="all: unset; cursor: pointer">
                                                <i class="las la-check-square"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('contest.submission.rejectAI',['contest' => $contest->id, 'submission' => $submission->id]) }}" method="post" class="d-inline-block">
                                            @csrf
                                            <button type="submit" class="d-flex action-btn" style="all: unset; cursor: pointer">
                                                <i class="las la-robot"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
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
    @if (config('app.livewire'))
        <livewire:sync-submission-component :global="$global" :contest="$contest" :lastCheck="$lastUpdated" />
    @endif
</div>

<script type='module'>
    const userId = {{ $global ? 'null' : \Auth::user()->id }}

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

    function failed() {
        var scalar = 12;
        var sadFace = confetti.shapeFromText({
            text: '😔',
            scalar
        });
        var duration = 6 * 1000;
        var animationEnd = Date.now() + duration;
        var skew = 1;

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        (function frame() {
            var timeLeft = animationEnd - Date.now();
            var ticks = Math.max(200, 500 * (timeLeft / duration));
            skew = Math.max(0.8, skew - 0.001);

            confetti({
                particleCount: 1,
                startVelocity: 0,
                ticks: ticks,
                origin: {
                    x: Math.random(),
                    // since particles fall down, skew start toward the top
                    y: -0.05
                },
                flat: true,
                shapes: [sadFace],
                gravity: randomInRange(0.4, 0.6),
                scalar: randomInRange(1, 6),
            });

            if (timeLeft > 0) {
                setTimeout(() => requestAnimationFrame(frame), 100)
            }
        }());
    }

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
        const idtag = row.find('#id');
        const datetimetag = row.find('#datetime');
        const usertag = row.find('#user');
        const titletag = row.find('#title');
        const langtag = row.find('#lang');
        const statustag = row.find('#status');
        const resulttag = row.find('#result');
        const testCasestag = row.find('#testCases');
        const resourcestag = row.find('#resources');

        if (data.user_id == userId) {
            idtag.html(`
                <a href="#" onclick="openModal(${data.id})">
                    #${data.id}
                </a>
            `)
        } else {
            idtag.text('#' + data.id);
        }
        datetimetag.text(data.datetime);
        const userName = {{ $contestService->inContest || $contest ? 'data.contest.competitor' : 'data.user' }};
        usertag.text(userName.length > 20 ? userName.substring(0, 20) + '...' : userName);
        titletag.text(data.problem.title.length > 30 ? data.problem.title.substring(0, 30) + '...' : data.problem
            .title);
        langtag.text(data.language);
        statustag.text(data.status);
        resulttag.text(data.result);
        testCasestag.text(data.testCases ?? '---');
        resourcestag.text(data.resources);
        titletag.attr('href', "{{ route('problem.show', ['problem' => -1], false) }}".replace('-1', data.problem
            .id));

        let style;

        switch (data.result) {
            case 'Accepted':
                style = "#0a0"
                testCasestag.text('All');
                break;

            case 'Error':
                style = "#f00"
                break;

            case 'Wrong answer':
                style = "#a00"
                break;

            case 'Compilation error':
            case 'Runtime error':
                style = "#aa0"
                break;

            case 'Ai detected':
            case 'Time limit':
            case 'Memory limit':
                style = "#00a"
                break;

            default:
                style = "grey"
        }
        resulttag.css('color', style);
        testCasestag.css('color', style);

        row.data('id', data.id);
        row.css('display', 'table-row');
        row.attr('id', 'row' + data.id);
        if (data.status != 'Judged' && data.status != 'Error')
            row.addClass('notJudged blink');
        else
            row.removeClass('notJudged blink');
    }
    window.updateSubmission = function(data) {
        var row = $('#row' + data.id);
        if (row.length == 0) {
            if (userId != null && userId != data.user_id) return
            row = $('#template-row').clone();
            updateRow(row, data);
            $('#table-body').prepend(row);
        }
        if (data.status != 'Judged' && data.status != 'Error') {
            updateRow(row, data);
        } else {
            var suspense = false;
            row.removeClass('blink')
            row.find('#status').text(data.status);
            row.find('#testCases').text('--');
            switch (data.result) {
                case 'Wrong answer':
                case 'Time limit':
                case 'Memory limit':
                case 'Runtime error':
                case 'Accepted':
                case 'Ai detected':
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
                    case 'Ai detected':
                    case 'Wrong answer':
                    case 'Time limit':
                    case 'Memory limit':
                    default:
                        failed()
                        break;
                    case 'Accepted':
                        confeti();
                        break;
                }
            }
            const bateria = function() {
                const text = row.find("#result").text();
                if (text.length < 5) {
                    row.find("#result").text(text + '🥁')
                    setTimeout(bateria, 800 + text.length * 100)
                } else {
                    if (data.result != 'Accepted') {
                        setTimeout(() => failed(), 400)
                    }
                    setTimeout(geraResultado, 1000)
                }
            }


            if (suspense) {
                row.find("#result").text('');
                setTimeout(bateria, 500)
            } else {
                geraResultado();
            }
        }
    }
    @if (!config('app.livewire'))
        const channel = "{{ $channel }}";
        window.addEventListener("load", function() {
            window.Echo.private(channel)
                .listen('NewSubmissionEvent', (data) => {
                    window.updateSubmission(data.data)
                })

            window.Echo.private(channel)
                .listen('UpdateSubmissionTestCaseEvent', (data) => {
                    window.updateSubmission(data.data)
                })

            window.Echo.private(channel)
                .listen('UpdateSubmissionEvent', (data) => {
                    window.updateSubmission(data.data)
                });
        })
    @endif
</script>
