@extends('layouts.boca')

@section('head')
<script>
window.MathJax= {
    processClass: "mathjax",
    ignoreClass: "no-mathjax",
    tex: {
        inlineMath: [['$', '$']]
    }
}
</script>
<script id="MathJax-script" async src="{{asset('js/mathjax/tex-chtml.js')}}"></script>
{!! htmlScriptTagJsApi() !!}

@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Submit a Problem:
            </b>
        </div>
        <div class="col text-end">
            <a href="{{route('problem.index')}}">Go Back</a>
        </div>
    </div>

    <form id="{{ getFormId() }}" method="post" action="@if(isset($problem->id)){{ route('problem.update',['problem'=>$problem->id])}}@else{{route('problem.store')}}@endif" id="form">
        @csrf
        @if(isset($problem->id))
        @method('PUT')
        @endif
        
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

        <div class="row">
            <div class="col">
                <label for="title" class="form-label">Title: </label><br />
                <input type="text" class="form-control" id="title" name="title"
                    value="{{old('title',$problem->title)}}"/>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <label for="author" class="form-label">Author: </label><br />
                <input type="text" class="form-control" id="author" name="author"
                    value="{{old('author',$problem->author)}}"/>
            </div>
            <div class="col-3">
                <label for="memory_limit" class="form-label">Memory Limit (MB): </label><br />
                <input type="number" class="form-control" id="memory_limit" name="memory_limit" 
                    value="{{old('memory_limit',$problem->memory_limit)}}"/>
            </div>
            <div class="col-3">
                <label for="time_limit" class="form-label">Time Limit (ms): </label><br />
                <input type="number" class="form-control" id="time_limit" name="time_limit" 
                    value="{{old('time_limit',$problem->time_limit)}}"/>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label for="description" class="form-label">Description: </label><br />
                <textarea class="markdown" id="description" name="description" style="width: 100%">{{old('description',$problem->description)}}</textarea>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label for="input_description" class="form-label">Input description: </label><br />
                <textarea class="markdown" id="input_description" name="input_description" style="width: 100%">{{old('input_description',$problem->input_description)}}</textarea>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label for="output_description" class="form-label">Output description: </label><br />
                <textarea class="markdown" id="output_description" name="output_description" style="width: 100%">{{old('output_description',$problem->output_description)}}</textarea>
            </div>
        </div>

        <p class="mt-3">
           {!! htmlFormButton('Submit', [
            'class' => "btn btn-primary"
           ]) !!}
        </p>
    </form>
@endsection

@section('script')

<script>


</script>
@endsection
