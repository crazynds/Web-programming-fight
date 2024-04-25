<div wire:poll.1s>
    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th class="text-center"><b>When</b></th>
                <th class="text-center"><b>Who</b></th>
                <th class="text-center"><b>Problem</b></th>
                <th class="text-center"><b>Lang</b></th>
                <th class="text-center"><b>Status</b></th>
                <th class="text-center"><b>Result</b></th>
                <th class="text-center"><b>Cases</b></th>
                <th class="text-center"><b>Resources</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($submitRuns as $submitRun)
                <tr wire:key="run-{{ $submitRun->id }}" data-id="{{ $submitRun->id }}"
                    @if ($submitRun->status != 'Judged' && $submitRun->status != 'Error') class="notJudged blink" @endif>
                    <td>
                        @can('view', $submitRun)
                            <a href="#" onclick="openModal({{ $submitRun->id }})">
                                #{{ $submitRun->id }}
                            </a>
                        @else
                            #{{ $submitRun->id }}
                        @endcan
                    </td>
                    <td class="px-1 text-center">
                        <small>
                            @if (\Carbon\Carbon::parse($submitRun->created_at)->format('d/m/Y') != (new DateTime())->format('d/m/Y'))
                                {{ \Carbon\Carbon::parse($submitRun->created_at)->format('d/m/Y') }}
                            @else
                                {{ \Carbon\Carbon::parse($submitRun->created_at)->format('H:i:s') }}
                            @endif
                        </small>
                    </td>
                    <td class="px-1">
                        {{ $submitRun->user->name }}
                    </td>
                    <td class="px-1">
                        <a href="{{ route('problem.show', ['problem' => $submitRun->problem->id], false) }}">
                            {{ $submitRun->problem->title }}
                        </a>
                    </td>
                    <td class="px-1">
                        {{ $submitRun->language }}
                    </td>
                    <td class="px-2">
                        <strong id="status">
                            {{ $submitRun->status }}
                        </strong>
                    </td>
                    <td class="px-2">
                        <span id="result"
                            @switch($submitRun->result)
                            @case('Accepted')
                                style="color:#0a0"
                                @break
                            @case('Error')
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
                                style="color:#00a"
                                @break
                            @default
                                style="color:grey"
                        @endswitch>
                            {{ $submitRun->result }}
                        </span>
                    </td>
                    <td class="px-2 text-center">
                        <small id="testCases">
                            @isset($waitingToBeJudged[$submitRun->id])
                                @for ($i = 1; $i < $waitingToBeJudged[$submitRun->id] && $i < 4; $i++)
                                    🥁
                                @endfor
                            @else
                                @switch($submitRun->result)
                                    @case('Accepted')
                                        <span style="color:#0a0">
                                            All
                                        </span>
                                    @break

                                    @case('Wrong answer')
                                        <span style="color:#a00">
                                            {{ $submitRun->num_test_cases + 1 }}
                                        </span>
                                    @break

                                    @case('Runtime error')
                                    @case('Time limit')

                                    @case('Memory limit')
                                        <span style="color:#00a">
                                            {{ $submitRun->num_test_cases + 1 }}
                                        </span>
                                    @break

                                    @case('Error')
                                    @case('Compilation error')
                                        ---
                                    @break

                                    @default
                                        ---
                                    @break
                                @endswitch
                            @endisset
                        </small>
                    </td>
                    <td class="px-2" style="font-size: 0.9em">
                        @if (isset($submitRun->execution_time) && $submitRun->status == 'Judged')
                            {{ number_format($submitRun->execution_time / 1000, 2, '.', ',') }}s
                        @else
                            --
                        @endif
                        |
                        @if (isset($submitRun->execution_memory) && $submitRun->status == 'Judged')
                            {{ $submitRun->execution_memory }} MB
                        @else
                            --
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @if ($submitRun->status == 'Judged' || $submitRun->status == 'Error')
                                @if ($submitRun->can_update)
                                    @if ($limit && $submitRun->status != 'Compilation error')
                                        <a href="{{ route('submitRun.rejudge', ['submitRun' => $submitRun->id], false) }}"
                                            class="d-flex action-btn">
                                            <i class="las la-redo-alt"></i>
                                        </a>
                                    @endif
                                @endif
                                @if ($submitRun->can_view)
                                    @if (isset($submitRun->output))
                                        <a href="{{ route('submitRun.show', ['submitRun' => $submitRun->id], false) }}"
                                            class="d-flex action-btn">
                                            <i class="las la-poll-h"></i>
                                        </a>
                                    @endif
                                @endif
                            @endif
                            @if ($submitRun->can_view)
                                @if ($submitRun->file_id != null)
                                    <a target="_blank"
                                        href="{{ route('submitRun.download', ['submitRun' => $submitRun->id], false) }}"
                                        class="d-flex action-btn">
                                        <i class="las la-file-download"></i>
                                    </a>
                                @endif
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
                <pre id="code" style="border: 1px black solid">Code...</pre>
            </div>
        </div>
    </div>
</div>


@script
    <script>
        function copyCode() {
            var range = document.createRange();
            range.selectNode(document.getElementById("code"));
            window.getSelection().removeAllRanges(); // clear current selection
            window.getSelection().addRange(range); // to select text
            document.execCommand("copy");
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
        var openModal = function() {}
        window.addEventListener("load", function() {

            openModal = function(id) {
                var url = '{{ route('api.submitRun.code', ['submitRun' => -1]) }}'.replace('-1', id)
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
                        $('#codeModal').find('#code').text(data.code)
                });
            }
        })
        $wire.on('myRunAccepted', () => {
            confeti()
        });
        $wire.on('myRunFailed', () => {
            failed()
        });
    </script>
@endscript
