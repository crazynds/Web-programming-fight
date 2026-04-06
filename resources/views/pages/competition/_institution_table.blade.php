<table border="1" style="margin:unset; width:100%">
    <thead>
        <tr>
            <th class="px-1"><b>#</b></th>
            <th class="px-2"><b>User</b></th>
            <th class="text-center px-2"><b>Solved</b></th>
            <th class="text-center px-2"><b>Bonus 🏆</b></th>
            <th class="text-center px-2"><b>Score</b></th>
            <th class="text-center px-2"><b>Attempts</b></th>
        </tr>
    </thead>
    <tbody>
        @forelse($memberStats as $i => $row)
            <tr @if($row->member->user_id === Auth::id()) style="background: lightsteelblue;" @endif>
                <td class="px-1">{{ $i + 1 }}º</td>
                <td class="px-2">
                    <a href="{{ route('user.profile', $row->member->user_id) }}">
                        {{ $row->member->user->name }}
                    </a>
                </td>
                <td class="text-center px-2">{{ $row->solved }}</td>
                <td class="text-center px-2">{{ $row->bonus }}</td>
                <td class="text-center px-2"><b>{{ $row->score }}</b></td>
                <td class="text-center px-2">{{ $row->attempts }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-2 text-center text-muted">No members in this league.</td>
            </tr>
        @endforelse
    </tbody>
</table>
