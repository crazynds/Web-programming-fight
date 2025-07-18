@extends('layouts.boca')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Creating a Team:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('team.index') }}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
        action="@if (isset($team->id)) {{ route('team.update', ['team' => $team->id]) }}@else{{ route('team.store') }} @endif">
        @csrf
        @if (isset($team->id))
            @method('PUT')
        @endif
        <div class="row">
            <div class="col">
                <label for="name" class="form-label">Team name: </label><br />
                <input type="text" name="name" class="form-control" maxlength="40"
                    value="{{ old('name', $team->name) }}" />
            </div>
            <div class="col-3">
                <label for="acronym" class="form-label">Team acronym: </label><br />
                <input type="text" maxlength="5" name="acronym" class="form-control"
                    value="{{ old('acronym', $team->acronym) }}" />
            </div>
        </div>        

        <div class="row mt-3">
            <div class="col">
                <label for="country" class="form-label">Country: </label><br />
                @php
                    $countries = [
                        'Brazil', 'Argentina', 'United States', 'Canada', 'Mexico',
                        'Colombia', 'Peru', 'Chile', 'Venezuela', 'Ecuador'
                    ];
                    sort($countries);
                @endphp
                <select name="country" id="country" class="form-select" required>
                    <option value="">Select a country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}" {{ old('country', $team->country ?? '') == $country ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col" id="state-field" style="display: none;">
                <label for="state" class="form-label">State: </label><br />
                <select name="state" id="state" class="form-select">
                    <option value="">Select a state</option>
                    @php
                        $states = [
                            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                            'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                            'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                            'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro',
                            'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia',
                            'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
                            'SE' => 'Sergipe', 'TO' => 'Tocantins'
                        ];
                    @endphp
                    @foreach($states as $code => $name)
                        <option value="{{ $code }}" {{ old('state', $team->state ?? '') == $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col">
                <label for="membersjson" class="form-label">Membros: </label><br />
                <input id="tags" name="membersjson" placeholder="Members nickname"
                    value="{{ old('membersjson', $team->membersjson()) }}">
                <small>
                    Write the member's github nickname and press enter.</small>
            </div>
            <div class="col-3">
                <label for="institution_acronym" class="form-label">Institution acronym: </label><br />
                <input type="text" maxlength="6" name="institution_acronym" class="form-control"
                    value="{{ old('institution_acronym', $team->institution_acronym) }}" />
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

        <div class="alert alert-info mt-3">
            <strong>Note about team members:</strong>
            <ul class="mb-0 mt-2">
                <li>Members need to have logged into the system at least once to receive an invitation.</li>
                <li>Invited members will need to accept the invitation in their team management section.</li>
                <li>You can invite multiple members by entering their usernames and pressing Enter.</li>
                <li>You don't need to add youself.</li>
            </ul>
        </div>

        <p class="mt-3">
            {!! htmlFormButton('Submit', [
                'class' => 'btn btn-primary',
            ]) !!}
        </p>
    </form>

@endsection


@section('script')
    <script type='module'>
        document.addEventListener('DOMContentLoaded', function() {
            const countrySelect = document.getElementById('country');
            const stateField = document.getElementById('state-field');
            const stateSelect = document.getElementById('state');

            // Toggle state field based on country selection
            function toggleStateField() {
                if (countrySelect.value === 'Brazil') {
                    stateField.style.display = 'block';
                    stateSelect.required = true;
                } else {
                    stateField.style.display = 'none';
                    stateSelect.required = false;
                    stateSelect.value = ''; // Clear state when country changes
                }
            }

            // Initial check
            toggleStateField();

            // Add event listener for country change
            countrySelect.addEventListener('change', toggleStateField);
        });

        window.addEventListener("load", function() {
            var input = document.querySelector('#tags')
            var tagify = new Tagify(input, {

            })
            $('form').one('submit', function(e) {
                e.preventDefault();
                // do your things ...

                setTimeout(() => {
                    $(this).submit();
                }, 200);
            });
        })
    </script>
@endsection
