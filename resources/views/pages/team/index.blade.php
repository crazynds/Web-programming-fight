@extends('layouts.boca')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Teams:
            </b>
        </div>
        @can('create', \App\Models\Team::class)
            <div class="col">
                <a style="float:right" href="{{ route('team.create') }}">
                    <button>New +</button>
                </a>
            </div>
        @endcan
    </div>

    <table border="1">
        <thead>
            <tr>
                <th style><b>#</b></th>
                <th class="text-center"><b>Name</b></th>
                <th class="text-center"><b>Members</b></th>
                <th class="text-center"><b>Role</b></th>
                <th style="text-align: end;"><b>Actions</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($teams as $team)
                <tr>
                    <td class="text-center">
                        [{{ $team->acronym }}]
                    </td>
                    <td class="px-2 text-center">
                        @if ($team->pivot->accepted == false)
                            <span class="text-info">
                            @else
                                <span class="text-success">
                        @endif
                        {{ $team->name }}
                        </span>
                    </td>
                    <td class="px-2 text-center">
                        <span class="">{{ $team->members_count }}</span>
                        @if ($team->invited_count > 0)
                            (<u class="text-info" title="Invited members awaiting to accept">{{ $team->invited_count }}</u>)
                        @endif
                    </td>
                    <td>
                        @if ($team->pivot->owner)
                            <span title="You are the owner of this team" style="cursor:help">
                                👑
                            </span>
                        @endif
                    </td>
                    <td class="px-2">
                        <div class="hstack gap-1">
                            @if ($team->pivot->accepted == false)
                                @can('modifyMembers', $team)
                                    <a href="{{ route('team.accept', ['team' => $team->id]) }}" class="d-flex action-btn">
                                        <i class="las la-check-circle"></i>
                                    </a>
                                    <a href="{{ route('team.deny', ['team' => $team->id]) }}" class="d-flex action-btn">
                                        <i class="lar la-times-circle"></i>
                                    </a>
                                @endcan
                            @else
                                @can('leave', $team)
                                    <a href="{{ route('team.leave', ['team' => $team->id]) }}" class="d-flex action-btn">
                                        <i class="las la-times-circle"></i>
                                    </a>
                                @endcan
                            @endif
                            @can('update', $team)
                                <a href="{{ route('team.edit', ['team' => $team->id]) }}" class="d-flex action-btn">
                                    <i class="las la-edit"></i>
                                </a>
                            @endcan
                            @can('delete', $team)
                                <div class="vr"></div>
                                <form action="{{ route('team.destroy', ['team' => $team->id]) }}" method="POST">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" class="d-flex bg-transparent" style="border:0; padding:0;">
                                        <i class="las la-trash"></i>
                                    </button>
                                </form>
                            @endcan
                            @cannot('modifyMembers', $team)
                                |In contest|
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
