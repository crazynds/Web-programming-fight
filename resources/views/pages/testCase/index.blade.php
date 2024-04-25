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
                {{ $problem->title }}
            </h1>
        </div>
        <div class="col">
            <a style="float:right" href="{{ route('problem.testCase.create', ['problem' => $problem->id]) }}">
                <button>New +</button>
            </a>
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

    <table border="1">
        <thead>
            <tr>
                <th><b>#</b></th>
                <th class="text-center"><b>Name</b></th>
                @can('update', $problem)
                    <th class="text-center"><b></b></th>
                @endcan
                <th class="text-center"><b>RuntimeError</b></th>
                <th class="text-center"><b>MemoryLimit</b></th>
                <th class="text-center"><b>TimeLimit</b></th>
                <th class="text-center"><b>WrongAnswer</b></th>
                <th class="text-center"><b>Accepts</b></th>
                <th class="text-center"><b>Public</b></th>
                <th class="text-center"><b>Validated</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($testCases as $testCase)
                <tr>
                    <td class="pr-2">
                        #{{ $testCase->position }}
                    </td>
                    <td class="px-2">
                        {{ $testCase->name }}
                    </td>
                    @can('update', $problem)
                        <td class="px-2">
                            <div class="hstack gap-1">
                                @if ($testCase->position > 1)
                                    <a href="{{ route('problem.testCase.down', ['problem' => $problem->id, 'testCase' => $testCase->id]) }}"
                                        class="d-flex action-btn">
                                        <i class="las la-angle-up"></i>
                                    </a>
                                @else
                                    <div class="d-flex">
                                        <i class="las la-angle-up"></i>
                                    </div>
                                @endif
                                <div class="vr"></div>
                                @if (!$loop->last)
                                    <a href="{{ route('problem.testCase.up', ['problem' => $problem->id, 'testCase' => $testCase->id]) }}"
                                        class="d-flex action-btn">
                                        <i class="las la-angle-down"></i>
                                    </a>
                                @else
                                    <div class="d-flex">
                                        <i class="las la-angle-down"></i>
                                    </div>
                                @endif
                            </div>
                        </td>
                    @endcan
                    <td class="px-2 text-center">
                        {{ $testCase->runtime_error_runs }}
                    </td>
                    <td class="px-2 text-center">
                        {{ $testCase->memory_limit_runs }}
                    </td>
                    <td class="text-center">
                        {{ $testCase->time_limit_runs }}
                    </td>
                    <td class="text-center">
                        {{ $testCase->wrong_answer_runs }}
                    </td>
                    <td class="text-center">
                        @if ($testCase->submit_runs_count == 0)
                            --%
                        @else
                            {{ round(($testCase->accepted_runs / $testCase->submit_runs_count) * 100, 2) }}%
                        @endif
                    </td>
                    <td class="text-center">
                        @if (!$testCase->validated)
                            No
                        @else
                            @if ($testCase->public)
                                Yes
                                <a href="{{ route('problem.testCase.edit.public', ['problem' => $problem->id, 'testCase' => $testCase->id]) }}"
                                    style="text-decoration:none !important;">
                                    <i class="las la-lock"></i>
                                </a>
                            @else
                                No
                                <a href="{{ route('problem.testCase.edit.public', ['problem' => $problem->id, 'testCase' => $testCase->id]) }}"
                                    style="text-decoration:none !important;">
                                    <i class="las la-unlock"></i>
                                </a>
                            @endif
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($testCase->validated)
                            <i class="las la-thumbs-up" style="color:green"></i>
                        @else
                            <i class="las la-thumbs-down" style="color:red"></i>
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @can('update', $problem)
                                <a href="{{ route('problem.testCase.show', ['problem' => $problem->id, 'testCase' => $testCase->id]) }}"
                                    class="d-flex action-btn">
                                    <i class="las la-eye"></i>
                                </a>
                                <div class="vr"></div>
                                <a href="{{ route('problem.testCase.input', ['problem' => $problem->id, 'testCase' => $testCase->id]) }}"
                                    target="_blank" class="d-flex action-btn">
                                    <i class="las la-sign-in-alt"></i>
                                </a>
                                <div class="vr"></div>
                                <a href="{{ route('problem.testCase.output', ['problem' => $problem->id, 'testCase' => $testCase->id]) }}"
                                    target="_blank" class="d-flex action-btn">
                                    <i class="las la-sign-out-alt"></i>
                                </a>
                                <div class="vr"></div>
                                <form
                                    action="{{ route('problem.testCase.destroy', ['problem' => $problem->id, 'testCase' => $testCase->id]) }}"
                                    method="POST">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;">
                                        <i class="las la-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row">
        <strong>
            *To validate a test case, you need to submit a solution that pass the test case
        </strong>
    </div>
@endsection
