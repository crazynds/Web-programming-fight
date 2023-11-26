@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Profile:
            </b>
        </div>
    </div>

    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-md-8 col-xl-4">

            <div class="card" style="border-radius: 15px;">
                <div class="card-body text-center">
                    <div class="mt-3 mb-4">
                        <img src="{{$user->avatar}}"
                            class="rounded-circle img-fluid" style="width: 100px;" />
                    </div>
                    <h4 class="mb-2">{{ $user->name }}</h4>
                    <p class="text-muted mb-4">@Github <span class="mx-2">|</span> <a
                            href="mailto:{{ $user->email }}">{{ $user->email }}</a></p>
                    <div class="d-flex justify-content-between text-center mt-5 mb-2">
                        <div>
                            <p class="mb-2 h5">{{ $problems_count }}</p>
                            <p class="text-muted mb-0">Problems Solved</p>
                        </div>
                        <div class="px-3">
                            <p class="mb-2 h5">{{ $accepted_count }}</p>
                            <p class="text-muted mb-0">Accepts</p>
                        </div>
                        <div>
                            <p class="mb-2 h5">{{ $attempts_count }}</p>
                            <p class="text-muted mb-0">Submissions</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
