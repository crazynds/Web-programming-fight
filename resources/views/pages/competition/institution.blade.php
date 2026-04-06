@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col-8">
            <b>Brasileirao de Programacao - {{ $institution->acronym }}</b>
        </div>
        <div class="col-4">
            @can('viewPulse')
                <a style="float:right; margin-left:5px" href="{{ route('competition.admin.index') }}">
                    <button>Admin</button>
                </a>
            @endcan
            <a style="float:right" href="javascript:history.back()">
                <button>← Back</button>
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Liga dos Bixos --}}
        <div class="col">
            <h5>Liga dos Bixos</h5>
            @include('pages.competition._institution_table', ['memberStats' => $bixosStats])
        </div>

        {{-- Liga dos Veteranos --}}
        <div class="col">
            <h5>Liga dos Veteranos</h5>
            @include('pages.competition._institution_table', ['memberStats' => $veteranosStats])
        </div>
    </div>
@endsection
