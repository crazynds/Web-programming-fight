@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Test Cases:
            </b>
        </div>
        <div class="col" style="text-align:center;">
            <h1>
                {{$problem->title}}
            </h1>
        </div>
        <div class="col">
            <a style="float:right" href="{{ route('problem.testcase.create',['problem' => $problem->id ]) }}">
                <button>New +</button>
            </a>
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th style="text-align: center;"><b>Id</b></th>
                <th style="text-align: center;"><b>RuntimeError</b></th>
                <th style="text-align: center;"><b>MemoryLimit</b></th>
                <th style="text-align: center;"><b>TimeLimit</b></th>
                <th style="text-align: center;"><b>Accepts</b></th>
                <th style="text-align: center;"><b>Public</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($testCases as $testCase)
                <tr>
                    <td class="px-1">
                        #{{ $testCase->position }}
                    </td>
                    <td class="px-1">
                        {{ $testCase->id }} 
                    </td>
                    <td class="px-1" style="text-align: center;">
                        0
                    </td>
                    <td class="px-1" style="text-align: center;">
                        0
                    </td>
                    <td style="text-align: center;">
                        0
                    </td>
                    <td style="text-align: center;">
                        0%
                    </td>
                    <td style="text-align: center;">
                        @if($testCase->public)
                            Yes
                        @else
                            No
                        @endif
                    </td>
                    <td class="px-1">
                        <div class="hstack gap-1">
                            @if($testCase->position>1)
                                <a href="#" class="d-flex" style="text-decoration:none !important;">
                                    <i class="las la-angle-up"></i>
                                </a>
                            @else
                                <div class="d-flex">
                                    <i class="las la-angle-up"></i>
                                </div>
                            @endif
                            <div class="vr"></div>
                            @if(!$loop->last)
                                <a href="#" class="d-flex" style="text-decoration:none !important;">
                                    <i class="las la-angle-down"></i>
                                </a>
                            @else
                                <div class="d-flex">
                                    <i class="las la-angle-down"></i>
                                </div>
                            @endif
                            <div class="vr"></div>
                            <a href="#" target="_blank" class="d-flex" style="text-decoration:none !important;">
                                <i class="las la-sign-in-alt"></i>
                            </a>
                            <div class="vr"></div>
                            <a href="#" target="_blank" class="d-flex" style="text-decoration:none !important;">
                                <i class="las la-sign-out-alt"></i>
                            </a>
                            <div class="vr"></div>
                            <form action="{{route('problem.testcase.destroy',['problem'=>$problem->id,'testcase'=> $testCase->id])}}" method="POST">
                                @method('DELETE')
                                @csrf
                                <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;">
                                    <i class="las la-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
