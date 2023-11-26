@extends('layouts.boca')

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
                <th style="text-align: center;" colspan="2"><b>User</b></th>
                <th style="text-align: center;"><b>Created At</b></th>
                <th style="text-align: center;"><b>Last Run</b></th>
                @if(\Auth::user()->isAdmin())
                <th style="text-align: end;"><b>Actions</b></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr>
                <td style="text-align: center;">
                    {{$user->id}}
                </td>
                <td style="text-align: center;">
                    <a href="{{route('user.profile',['user'=>$user->id])}}">{{$user->name}}</a>
                </td>
                <td>
                    <img src="{{ $user->avatar }}" class="rounded-circle" style="width: 22px;height:22px;"
                            alt="Avatar {{$user->name}}" />
                </td>
                <td style="text-align: center;">
                    {{$user->created_at->format('m/Y')}}
                </td>
                <td style="text-align: center;">
                    @if($user->lastRun)
                        {{$user->lastRun->created_at->format('m/Y')}}
                    @else
                        --/--
                    @endif
                </td>
                @can('update',$user)
                <td style="text-align: center;">
                </td>
                @endcan
            </tr>
            @endforeach
        </tbody>
    </table>

@endsection