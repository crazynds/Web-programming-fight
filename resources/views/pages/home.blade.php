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
            Do the login to access all pages.
        </h3>
    @endguest

    @auth

        <div class="row">
            <div class="col-8">
                <h3>
                    v1.0.1 - !
                </h3>
                <p>
                    Date: ----
                    By: Crazynds
                </p>
                <p>
                    Features ✨
                <ul>
                    <li>Load problems using ZIP.</li>
                    <li>Created contest page!</li>
                </ul>
                </p>
                <p>
                    Changes ⚙️
                <ul>
                    <li>Best algorithm to download problems in ZIP format.</li>
                </ul>
                </p>
                <p>
                    Fixes 🐛
                <ul>
                    <li>Increased area size of Home button.</li>
                    <li>Added 'Go Back' buttons in some forgotten areas.</li>
                </ul>
                </p>
            </div>
        </div>
        <hr>

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
    @endauth
@endsection
