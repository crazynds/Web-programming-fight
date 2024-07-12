@extends('layouts.boca')


@section('head')
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col">
            <x-ballon />
            <b>
                Home
            </b>
            <x-ballon />

        </div>
    </div>
    @guest

        <h3>
            Login to access the platform!
        </h3>
        <p>
            Note that we use third-party github login to ensure user security, so no registration on the platform is necessary.
            We
            also do not store passwords or any sensitive user information in our database.
        </p>
        <p>
            You can login by clicking on the github icon in the top right corner of the page.
        </p>
    @endguest

    @auth
        @if ($contestService->inContest)
            <div class="row">
                <div class="col-8">
                    <h3>
                        {{ $contestService->contest->title }}
                    </h3>
                    <hr />
                    <div class="row mathjax">
                        {{ Illuminate\Mail\Markdown::parse($contestService->contest->description) }}
                    </div>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-8">
                    <h3>
                        v1.0.1 - The Big Challenge! ⚔️💥🗡️🏆
                    </h3>
                    <p>
                        Date: 09-07-2024
                        By: Crazynds
                    </p>
                    <p>
                        Features ✨
                    <ul>
                        <li>Created fast problem import using ZIP files.</li>
                        <li>Created contest page!</li>
                        <li>Created contest mode and all contest related features! 🎉</li>
                        <li>Added C to the available languages!</li>
                    </ul>
                    </p>
                    <p>
                        Changes ⚙️
                    <ul>
                        <li>FAQ and About options are no longer displayed while the pages do not exist.</li>
                        <li>The multipliers in python and pypy are now less strict.</li>
                        <li>Now we have a lot more result status to understand what happen with the submission.</li>
                        <li>Refactored all permissions levels.</li>
                    </ul>
                    </p>
                    <p>
                        Styles 🎨
                    <ul>
                        <li>Added a custom style to scroll bar.</li>
                        <li>Added 'Go Back' buttons in some forgotten areas.</li>
                        <li>Increased area size of Home button.</li>
                        <li>Create a custom style para contest mode!</li>
                        <li>Problem layout improved.</li>
                        <li>Leaderboard layout improved.</li>
                        <li>Navbar layout improved.</li>
                        <li>Language multiplier table added to the submission form.</li>
                    </ul>
                    </p>

                    <p>
                        Fixes 🐛
                    <ul>
                        <li>Fixed crashes when downloading a problem with big inputs.</li>
                        <li>Fixed bugs when using teams with contest.</li>
                        <li>Now the server won't save the file if it's not a valid text file.</li>
                        <li>Fixed a bug when you couldn't download or see a old code.</li>
                        <li>Fixed a bug when you couldn't see why compilation error happens.</li>
                    </ul>
                    </p>
                </div>
            </div>
            <hr style="margin-top: 120px" />

            <div class="row">
                <div class="col-8">
                    <h3>
                        v1.0.0 - Lets the party begin! 🎉🎉🎉
                    </h3>
                    <p>
                        Date: 29-04-2024
                        By: Crazynds
                    </p>
                    <p>
                        Features ✨
                    <ul>
                        <li>Added support for Python 3.11 and Pypy 3.10</li>
                        <li>Added party comemoration for accepted submissions</li>
                        <li>Added sadface rain for non accepted submissions</li>
                        <li>Created 'scorer' to rank the submissions</li>
                        <li>Created a global rank </li>
                        <li>Created this update list 🥳</li>
                    </ul>
                    </p>
                    <p>
                        Changes ⚙️
                    <ul>
                        <li>Enchanced Profile page and added a list of all solved problems by the user</li>
                        <li>Change submissions page to automatically update using sockets and broadcast messages</li>
                    </ul>
                    </p>
                    <p>
                        Fixes 🐛
                    <ul>
                        <li>Small bugs that I dont remember</li>
                    </ul>
                    </p>
                </div>
                <div class="col-6">

                </div>
            </div>
        @endif
    @endauth
@endsection
