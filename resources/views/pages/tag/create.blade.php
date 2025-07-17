@extends('layouts.boca')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Creating a Tag:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('tag.index') }}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
        action="@if (isset($tag->id)) {{ route('tag.update', ['tag' => $tag->id]) }}@else{{ route('tag.store') }} @endif">
        @csrf
        @if (isset($tag->id))
            @method('PUT')
        @endif
        <div class="row">
            <div class="col">
                <label for="name" class="form-label">Tag name: </label><br />
                <input type="text" name="name" class="form-control" maxlength="40"
                    value="{{ old('name', $tag->name) }}" />
            </div>
            <div class="col">
                <label for="alias" class="form-label">Alias: </label><br />
                <input type="text" name="alias" class="form-control"
                    value="{{ old('alias', $tag->alias) }}" />
            </div>
        </div>
        <div class="row mt-3">
            <div class="col">
                <h4 class="text-center">Problems</h4>
                <select multiple="multiple" id="problems" name="problems[]">
                    @foreach ($problems as $problem)
                        <option value="{{ $problem->id }}" @if (in_array($problem->id, old('problems', $tag->problems()->pluck('problems.id')->toArray() ?? []))) selected @endif>
                            #{{ $problem->id }} - {{ $problem->title }}</option>
                    @endforeach
                </select>
                <label for="type" class="form-label mt-2">Type: </label><br />
                <select name="type" class="form-select select2" required>
                    @foreach (App\Enums\TagTypeEnum::asArray() as $name => $code)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($errors->any())
            <div class="row p-3">
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
    <script type='module'>
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
                },
            })
        })
    </script>
@endsection
