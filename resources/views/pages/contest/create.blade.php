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
    </div>

    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data" action="{{ route('contest.store') }}">
        @csrf

        <div class="row">
            <div class="col">
                <label for="title" class="form-label">Title: </label><br />
                <input type="text" class="form-control" id="title" name="title"
                    value="{{ old('title', $contest->title) }}" required />
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <label for="start_time" class="form-label">Start Time: </label><br />
                <input type="datetime-local" class="form-control" id="start_time" name="start_time"
                    value="{{ old('start_time', $contest->start_time) }}"
                    min="{{ now()->addMinutes(10)->format('Y-m-d\TH:i') }}" required />
            </div>
            <div class="col-3">
                <label for="duration" class="form-label">Duration: (Minutes)</label><br />
                <input type="number" class="form-control" id="duration" name="duration"
                    value="{{ old('duration', $contest->duration) ?? 120 }}" min="10" required />
            </div>
            <div class="col-3">
                <label for="blind_time" class="form-label">Blind Time: </label><br />
                <select name="blind_time" class="form-select" required>
                    @for ($i = 0; $i <= 240; $i += 20)
                        <option value="{{ $i }}" @if (old('blind_time', $contest->blind_time) == $i) selected @endif>
                            {{ $i }} Minutes
                        </option>
                    @endfor
                    @if (old('blind_time', $contest->blind_time) && ($contest->blind_time > 240 || $contest->blind_time % 20 != 0))
                        <option value="{{ $contest->blind_time }}" selected>
                            {{ $contest->blind_time }} Minutes
                        </option>
                    @endif
                </select>
            </div>
            <div class="col-3">
                <label for="penality" class="form-label">Penalty: </label><br />
                <input type="number" class="form-control" id="penality" name="penality"
                    value="{{ old('penality', $contest->penality) ?? 0 }}" min="0" required />
            </div>
        </div>


        <div class="row">
            <div class="col-6">
                <label for="is_private" class="form-label">Private: </label>
                <input type='hidden' id="hidden_is_private" value='0' name='is_private'>
                <input type="checkbox" id="is_private" value='1' name="is_private"
                    @if (old('is_private', $contest->is_private)) checked @endif />
                <input type="text" class="form-control" id="password" name="password"
                    value="{{ old('password', $contest->password) }}" @if (!old('is_private', $contest->is_private)) disabled @endif
                    placeholder="Password" />
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h4>Rules:</h4>

                <ul>
                    <li>
                        <input type='hidden' id="hidden_parcial_solution" value='0' name='parcial_solution'>
                        <input type="checkbox" id="parcial_solution" value='1' name="parcial_solution"
                            @if (old('parcial_solution', $contest->parcial_solution)) checked @endif />
                        <label for="parcial_solution" class="form-label" style="cursor: help;text-decoration: underline;"
                            title="You only start earning points if you get at least 30% of the cases correct, with a maximum of 60% of a complete accept for all less one cases.">
                            Partial solutions are allowed. </label>
                    </li>
                    <li>
                        <input type='hidden' id="hidden_show_wrong_answer" value='0' name='show_wrong_answer'>
                        <input type="checkbox" id="show_wrong_answer" value='1' name="show_wrong_answer"
                            @if (old('show_wrong_answer', $contest->show_wrong_answer)) checked @endif />
                        <label for="show_wrong_answer" class="form-label" style="cursor: help;text-decoration: underline;"
                            title="Show difference output in Wrong Answer between the correct output and your solution output.">
                            Show difference output in Wrong Answer.
                        </label>
                    </li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <h4 class="text-center">Problems</h4>
                <select multiple="multiple" id="problems" name="problems[]">
                    @foreach ($problems as $problem)
                        <option value="{{ $problem->id }}">
                            #{{ $problem->id }} - {{ $problem->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <h4 class="text-center">Languages</h4>
                <select multiple="multiple" id="languages" name="languages[]">
                    @foreach (App\Enums\LanguagesType::list() as $name => $code)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

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


        <p class="mt-3">
            {!! htmlFormButton('Submit', [
                'class' => 'btn btn-primary',
            ]) !!}
        </p>
    </form>

@endsection


@section('script')
    <script>
        window.addEventListener("load", function() {
            $('#problems').multiSelect({
                selectableHeader: "<label>Avaliable</label><input type='text' class='search-input' autocomplete='off' placeholder='Search'>",
                selectionHeader: "<label>Selected</label><input type='text' class='search-input' autocomplete='off' placeholder='Search'>",
                afterInit: function(ms) {
                    var that = this,
                        $selectableSearch = that.$selectableUl.prev(),
                        $selectionSearch = that.$selectionUl.prev(),
                        selectableSearchString = '#' + that.$container.attr('id') +
                        ' .ms-elem-selectable:not(.ms-selected)',
                        selectionSearchString = '#' + that.$container.attr('id') +
                        ' .ms-elem-selection.ms-selected';

                    that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
                        .on('keydown', function(e) {
                            if (e.which === 40) {
                                that.$selectableUl.focus();
                                return false;
                            }
                        });

                    that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
                        .on('keydown', function(e) {
                            if (e.which == 40) {
                                that.$selectionUl.focus();
                                return false;
                            }
                        });
                },
                afterSelect: function() {
                    this.qs1.cache();
                    this.qs2.cache();
                },
                afterDeselect: function() {
                    this.qs1.cache();
                    this.qs2.cache();
                }
            })
            $('#languages').multiSelect({
                selectableHeader: "<label>Avaliable</label><input type='text' class='search-input' autocomplete='off' placeholder='Search'>",
                selectionHeader: "<label>Selected</label><input type='text' class='search-input' autocomplete='off' placeholder='Search'>",
                afterInit: function(ms) {
                    var that = this,
                        $selectableSearch = that.$selectableUl.prev(),
                        $selectionSearch = that.$selectionUl.prev(),
                        selectableSearchString = '#' + that.$container.attr('id') +
                        ' .ms-elem-selectable:not(.ms-selected)',
                        selectionSearchString = '#' + that.$container.attr('id') +
                        ' .ms-elem-selection.ms-selected';

                    that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
                        .on('keydown', function(e) {
                            if (e.which === 40) {
                                that.$selectableUl.focus();
                                return false;
                            }
                        });

                    that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
                        .on('keydown', function(e) {
                            if (e.which == 40) {
                                that.$selectionUl.focus();
                                return false;
                            }
                        });
                },
                afterSelect: function() {
                    this.qs1.cache();
                    this.qs2.cache();
                },
                afterDeselect: function() {
                    this.qs1.cache();
                    this.qs2.cache();
                }
            })
        });

        $('#is_private').change(function() {
            const val = this.checked;
            $('#password').prop('disabled', !val);
            $('#hidden_is_private').prop('disabled', val);
        });
        $('#parcial_solution').change(function() {
            $('#hidden_parcial_solution').prop('disabled', this.checked);
        });
        $('#show_wrong_answer').change(function() {
            $('#hidden_show_wrong_answer').prop('disabled', this.checked);
        });
    </script>
@endsection
