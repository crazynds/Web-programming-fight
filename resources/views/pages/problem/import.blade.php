@extends('layouts.boca')

@section('head')
    <script>
        window.MathJax = {
            processClass: "mathjax",
            ignoreClass: "no-mathjax",
            tex: {
                inlineMath: [
                    ['$', '$']
                ]
            }
        }
    </script>
    <script id="MathJax-script" async src="{{ asset('js/mathjax/tex-chtml.js') }}"></script>
    {!! htmlScriptTagJsApi() !!}
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Import a Problem:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('problem.index') }}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" action="{{ route('problem.import') }}">
        @csrf

        <div class="row">
            <div class="col">
                <h3>
                    Zip Problem:
                </h3>
                <input type="file" id="files1" name="problem" style="max-width:100%" />
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
    <script></script>
@endsection
