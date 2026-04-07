@extends('layouts.base')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <b>
                Brasileirao de Programacao - How It Works
            </b>
        </div>
        <div class="col text-end">
            <a href="{{ route('competition.brasileirao') }}">Go Back</a>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-header">
            <h3>How It Works</h3>
        </div>
        <div class="card-body">
            <p>When you register for the Brasileirao de Programacao:</p>
            <ul>
                <li>All users from the same institution will participate as a single team</li>
                <li>You can register as one of three roles:</li>
                <ul>
                    <li><strong>Coach</strong>: Experienced mentor or instructor</li>
                    <li><strong>Bixo</strong>: Beginner with up to 1 year of programming experience</li>
                    <li><strong>Veteran</strong>: Experienced programmer with more than 1 year of programming experience</li>
                </ul>
            </ul>
            <p><strong>Scoring and Ranking:</strong></p>
            <p>Institutions earn points through two sources: <strong>Weekly Challenges</strong> and <strong>Contests</strong>.</p>

            <p><strong>Weekly Challenges:</strong></p>
            <ul>
                <li><strong>+1 point</strong> if at least one member of the institution solved the challenge</li>
                <li><strong>+1 bonus point</strong> if the 1st place in the challenge leaderboard belongs to the institution. If multiple institutions are within <strong>5% of the 1st place metric</strong>, all of them are considered a technical tie and all receive the bonus point</li>
                <li><strong>Solutions</strong>: the number of unique members who submitted an accepted solution is added to the institution's solution count</li>
            </ul>

            <p><strong>Contests:</strong></p>
            <ul>
                <li><strong>+1 point</strong> for each unique problem solved by at least one member of the institution</li>
                <li><strong>+1 bonus point</strong> for institutions finishing in 1st, 2nd, or 3rd place in the contest ranking</li>
            </ul>
            <p><strong>Challenge Ranking Categories:</strong></p>
            <ul>
                <li><strong>Submission Time</strong>: the earliest accepted submission wins (lower elapsed time from challenge start is better)</li>
                <li><strong>CPU Time</strong>: the submission with the lowest execution time wins</li>
                <li><strong>Memory Peak</strong>: the submission with the lowest memory usage wins</li>
                <li><strong>Code Lines</strong>: the submission with the fewest lines of code wins. The following are <strong>not counted</strong>: empty lines, lines containing only <code>{</code> or <code>}</code>, and preprocessor directives (<code>#include</code>, <code>#define</code>, etc.). In C++ and C, the code is first preprocessed to expand macros (so code hidden behind <code>#define</code> aliases is properly counted). In C++, Java, and C, each <code>;</code> counts as a separate statement — e.g. two statements on one line count as two lines. Semicolons inside <code>for(...)</code> loop headers are <strong>not</strong> counted (e.g. <code>for(int i=0; i&lt;n; i++)</code> counts as one line, not three).</li>
                <li><strong>Test Cases Passed</strong>: the submission that passes the most test cases wins — the submission does not need to be fully correct. Only submissions that pass <strong>at least 5 test cases</strong> are considered valid and appear on the leaderboard. For institution scoring, all unique members with at least 5 test cases passed count toward the institution's solutions total</li>
            </ul>
            <p><strong>Fair Play Rules:</strong></p>
            <ul>
                <li>Only the best submission per user is considered in each challenge leaderboard</li>
                <li>Institutions are encouraged to share knowledge and tips about problems, as long as each solution was genuinely coded by its author</li>
                <li>Use of bots or fake accounts to inflate submission counts may result in account bans and deduction of points from the institution</li>
            </ul>
        </div>
    </div>
@endsection
