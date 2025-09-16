@extends('layouts.base')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @php($user = Auth::user() ?? \App\Models\User::guest())
    <div class="row mb-4">
        <div class="col-8">
            <x-ballon />
            <b>
                Problems:
            </b>
            @if(!$contestService->inContest)
                <form style="display: inline-block;" col="row">
                    @if ($vjudgeService->isEnabled())
                        <div style="display: inline-block;">
                            <select class="form-select select2" name="onlineJudge">
                                <option value="" @if (!($onlineJudge ?? false)) selected @endif>Local</option>
                                @foreach ($vjudgeService->avaliableJudges() as $judge)
                                    <option value="{{ $judge }}" @if (($onlineJudge ?? false) == $judge) selected @endif>
                                        {{ $judge }}</option>
                                @endforeach
                                <input type="hidden" name="tag" value="{{ $tag ?? '' }}">
                            </select>
                        </div>
                    @endif
                    <div style="display: inline-block;">
                        <input class="form-control" name="search" value="{{ $search ?? '' }}" />
                    </div>
                    <div style="display: inline-block;">
                        <button type="submit">Search</button>
                    </div>
                </form>
            @endif
        </div>
        <div class="col-4">
            @if (!$contestService->inContest)
                @can('create', \App\Models\Problem::class)
                    <a style="float:right" href="{{ route('problem.create') }}">
                        <button>New +</button>
                    </a>
                @endcan
                @if ($user??->isAdmin())
                    <a style="float:right; margin-right: 5px;" href="{{ route('problem.import') }}">
                        <button>Import +</button>
                    </a>
                    <a style="float:right; margin-right: 5px;" href="{{ route('problem.import.sbc') }}">
                        <button>Import SBC +</button>
                    </a>
                @endif
            @endif
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th class="px-1"><b>#</b></th>
                <th class="text-center px-2"><b>Title</b></th>
                @if (!$contestService->inContest)
                    <th class="text-center px-2"><b>Difficulty</b></th>
                @endif
                {{-- <th class="text-center px-2"><b>Mem</b></th>
                <th class="text-center px-2"><b>Time</b></th> --}}
                <th class="text-center px-2"><b>Accepts</b></th>
                {{-- <th class="text-center px-2"><b>Attempts</b></th> --}}
                {{-- @if (!$contestService->inContest)
                    <th class="text-center px-2"><b>Maintainer</b></th>
                @endif --}}
                <th style="text-align: end;" class="px-2"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @php($number = 'A')
            @foreach ($problems as $problem)
                <tr
                    @if ($problem->visible == false && !$contestService->inContest) class="star-row bg-black" style="--bs-bg-opacity: 0.125;"
                @elseif($problem->my_accepted_submissions > 0) class="star-row bg-success" style="--bs-bg-opacity: 0.125;" @endif>
                    <td class="pr-2">
                        @if ($contestService->inContest)
                            <b class="px-2" style="font-size: 1.6em">
                                {{ $number++ }}
                        </b @else #{{ $problem->id }} @endif
                    </td>
                    <td class="px-2">
                        <a href="{{ route('problem.show', ['problem' => $problem->id]) }}">
                            {{ Str::limit($problem->title, 30) }}
                        </a>
                    </td>
                    @if (!$contestService->inContest)
                        <td>
                            <input type="hidden" class="star-rating rating" data-show-clear="false"
                                id="rating-{{ $problem->id }}" data-problem-id="{{ $problem->id }}"
                                data-show-caption="false" data-size="xs" value="{{ $problem->rating / 2.0 }}"
                                data-original-value="{{ $problem->rating / 2.0 }}"
                                @if ($problem->my_accepted_submissions == 0) data-readonly="true" data-my-ratting=""
                                @else data-my-ratting="{{ ($rating[$problem->id]?->value ?? $problem->rating) / 2.0 }}" @endif>
                        </td>
                    @endif
                    {{-- <td class="px-2 text-center">
                        {{ $problem->memory_limit }}MB
                    </td>
                    <td class="px-2 text-center">
                        {{ $problem->time_limit / 1000 }}s
                    </td> --}}
                    <td class="text-center">
                        @if ($problem->submissions_count == 0)
                            --%
                        @else
                            {{ round(($problem->accepted_submissions / $problem->submissions_count) * 100, 2) }}%
                        @endif
                    </td>
                    {{-- <td class="text-center">
                        {{ $problem->submissions_count }}
                    </td>
                    @if (!$contestService->inContest)
                        <td class="text-center" class="px-2">
                            @if ($problem->user)
                                <a href="{{ route('user.profile', ['user' => $problem->user->id]) }}">
                                    {{ $problem->user->name }}
                                </a>
                            @else
                                ------
                            @endif
                        </td>
                    @endif --}}
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @if ($contestService->inContest)
                                <a href="{{ route('contest.problem.show', ['problem' => $problem->id]) }}"
                                    title="View problem" class="d-flex action-btn">
                                    <i class="las la-search"></i>
                                </a>
                            @else
                                @can('view', $problem)
                                    <a href="{{ route('problem.show', ['problem' => $problem->id]) }}" title="View problem"
                                        class="d-flex action-btn">
                                        <i class="las la-search"></i>
                                    </a>
                                    @if ($problem->ranks_count > 0)
                                        <div class="vr"></div>
                                        <a href="{{ route('problem.podium', ['problem' => $problem->id]) }}" title="Ranking"
                                            class="d-flex action-btn">
                                            <i class="las la-trophy"></i>
                                        </a>
                                    @endif
                                    <div class="vr"></div>
                                        <a href="{{ route('problem.testCase.index', ['problem' => $problem->id]) }}"
                                            title="Test cases" class="d-flex action-btn">
                                            <i class="las la-vial"></i>
                                        </a>
                                @endcan
                                @can('download', $problem)
                                    <div class="vr"></div>
                                    <a href="{{ route('problem.download', ['problem' => $problem->id]) }}"
                                        title="Download this problem" target="_blank" class="d-flex action-btn">
                                        <i class="las la-file-archive"></i>
                                    </a>
                                @endcan
                                @can('update', $problem)
                                    @if ($user?->isAdmin())
                                        <div class="vr"></div>
                                        <a href="{{ route('problem.scorer.index', ['problem' => $problem->id]) }}"
                                            title="Edit scores" class="d-flex action-btn">
                                            <i class="las la-star"></i>
                                        </a>
                                    @endif

                                    <div class="vr"></div>
                                    <a href="{{ route('problem.edit', ['problem' => $problem->id]) }}"
                                        title="Edit this problem" class="d-flex action-btn">
                                        <i class="las la-edit"></i>
                                    </a>

                                    <div class="vr"></div>
                                    <a href="{{ route('problem.public', ['problem' => $problem->id]) }}"
                                        title="Enable/Disable problem" class="d-flex action-btn">
                                        @if ($problem->visible == false)
                                            <i class="las la-eye"></i>
                                        @else
                                            <i class="las la-eye-slash"></i>
                                        @endif
                                    </a>
                                @endcan
                                @can('delete', $problem)
                                    <div class="vr"></div>
                                    <form action="{{ route('problem.destroy', ['problem' => $problem->id]) }}"
                                        method="POST">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;"
                                            title="Delete this problem">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pt-3">
        {{ method_exists($problems, 'links') ? $problems->links() : '' }}
    </div>
@endsection

@section('script')
    <script type='module'>
        $(document).ready(function() {
            var edit = false;

            $('.star-row').on('mouseenter', function() {
                const $this = $(this).find('.star-rating');
                const myRating = $this.data('my-ratting');
                if (!myRating) return
                $($this).rating('update', myRating);
                edit = true
            });

            $('.star-row').on('mouseleave', function() {
                const $this = $(this).find('.star-rating');
                const originalRating = $this.data('original-value');
                edit = false
                $($this).rating('update', originalRating);
            });

            MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

            var trackChange = function(element) {
                var observer = new MutationObserver(function(mutations, observer) {
                    if (mutations[0].attributeName == "value") {
                        $(element).trigger("change");
                    }
                });
                observer.observe(element, {
                    attributes: true
                });
            }

            // Hidden input does not trigger change events, so this is needed to trigger it.
            $(".star-rating").each((idx, el) => trackChange(el));


            $('.star-rating').change(function() {
                if (!edit) return;
                const myRating = $(this).data('my-ratting');
                if (parseInt(myRating) == parseInt(this.value)) {
                    return
                }
                $(this).data('my-ratting', this.value);
                console.log('update', this.value)
                $.ajax({
                    type: "POST",
                    url: "{{ route('problem.rating.store', ['problem' => -1]) }}".replace('-1',
                        $(this).data('problem-id')),
                    data: {
                        value: parseInt(this.value * 2)
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            })
        })
    </script>
@endsection
