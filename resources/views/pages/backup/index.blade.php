@extends('layouts.boca')

@section('head')
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Backup:
            </b>
        </div>
    </div>
    <!-- upload of a single file -->
    <div class="row">
        <div class="col">
            <form id="{{ getFormId() }}" method="post" enctype="multipart/form-data"
                action="{{ route('api.backup.upload') }}">
                @csrf

                <label for="backup" class="form-label">Upload Backup: </label><br />
                <input type="file" class="form-control" name="backup" required />
                
                <p class="mt-3">
                    {!! htmlFormButton('Submit', [
                        'class' => 'btn btn-primary',
                    ]) !!}
                </p>
            </form>

        </div>
        <div class="col">
            <label for="backup" class="form-label">Current Backup: </label><br />
            <div class="hstack gap-1">
                <a href="{{ route('api.backup.start')}}" title="Start Backup" class="d-flex action-btn">
                    <button>Start Backup</button>
                </a>
                @if(file_exists(storage_path('backup').'/last_backup.zip'))
                <a href="{{ route('api.backup.download')}}" title="Download backup" class="d-flex action-btn">
                    <button>Download Backup</button>
                </a>
                @endif
            </div>

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

@endsection
