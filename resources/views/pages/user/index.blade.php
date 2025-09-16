@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Users:
            </b>
        </div>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th style><b>#</b></th>
                <th class="text-center" colspan="2"><b>User</b></th>
                <th class="text-center"><b>Created At</b></th>
                <th class="text-center"><b>Last Run</b></th>
                @if(\Auth::user()->isAdmin())
                <th style="text-align: end;"><b>Actions</b></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr>
                <td class="text-center">
                    {{$user->id}}
                </td>
                <td class="text-center">
                    <a href="{{route('user.profile',['user'=>$user->id])}}">{{$user->name}}</a>
                </td>
                <td>
                    <img src="{{ $user->avatar }}" class="rounded-circle" style="width: 22px;height:22px;"
                            alt="Avatar {{$user->name}}" />
                </td>
                <td class="text-center">
                    {{$user->created_at->format('m/Y')}}
                </td>
                <td class="text-center">
                    @if($user->lastRun)
                        {{$user->lastRun->created_at->format('m/Y')}}
                    @else
                        --/--
                    @endif
                </td>
                @can('update',$user)
                <td class="text-center">
                </td>
                @endcan
            </tr>
            @endforeach
        </tbody>
    </table>

@endsection