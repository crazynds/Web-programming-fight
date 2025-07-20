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
                        <div clas="col">
                            {{ Illuminate\Mail\Markdown::parse($contestService->contest->description) }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            @php
                $changelogs = [
                    [
                        'version' => 'v1.1.1',
                        'title' => '!',
                        'date' => '18-07-2025',
                        'author' => 'Crazynds',
                        'features' => [
                            'Added Tag crud for admins.',
                            'Added leaderboard filters.'
                            'Added manual judge in contest admin pannel.'
                            'Added AI detection on contest submissions.'
                            'Add fields location information required in teams creation.'
                        ],
                        'changes' => [
                        ],
                        'styles' => [
                            'New background in new clarifications in admin pannel.'
                        ],
                        'fixes' => [
                            'When sending a binary as auto detect result in an internal error.',
                            'When a new team enters the contest, the leaderboard does not update.'
                        ]
                    ],
                    [
                        'version' => 'v1.1.0',
                        'title' => 'Fancy Update!',
                        'date' => '17-05-2025',
                        'author' => 'Crazynds',
                        'features' => [
                            'Integration with VJudge. (Not available yet)',
                            'Livewire can be used instead of WebSockets.',
                            'Added pagination in problem list.',
                            'Added title search in problem list.',
                            'Added the star button on header.',
                            'Added support to Java OpenJDK 24.',
                            'Added tag pages.'
                        ],
                        'changes' => [
                            'Python and PyPy is now compiled before execution.',
                            'Python is now version 3.13',
                            'Pypy is now version 3.11',
                            'Rating system works in index problem page now.',
                            'All C++ ploblens include fast IO by default.'
                        ],
                        'styles' => [
                            'JQuery is not imported manually anymore.',
                            'Select2 plugin is now used.',
                            'Contest leaderboad now auto updates itself and has some fancy animations.'
                        ],
                        'fixes' => [
                            'Compilation time and compilation memory is now limited.',
                            'Home screen.',
                            'Fixed a bug when enter in a contest with different timezone.'
                        ]
                    ],
                    [
                        'version' => 'v1.0.2',
                        'title' => 'Default Update!',
                        'date' => '20-01-2025',
                        'author' => 'Crazynds',
                        'features' => [
                            'Added difficulty in all problems.',
                            'If you get AC on a problem, you can rate how difficult it is.',
                            'Added option to import SBC maraton problems.',
                            'Added diff program to test differents outcomes.',
                            'Added tags to problems, even if you don\'t see them.',
                            'Added ability to add an explanation for each test case.'
                        ],
                        'changes' => [
                            'Now when you edit a test case you cannot change its name unless you know our tricks... 👀',
                            'Standardized execution within NSJail.'
                        ],
                        'styles' => [
                            'Now we have stars in each problem ✨',
                            'Images in problems description are now horizontally centered'
                        ],
                        'fixes' => [
                            'Fixed a bug when you try to add a new test case you get a error 500 page.',
                            'Fixed a bug to show preview of image in file manager.',
                            'Fixed a bug when a submission get stuck in some loop resulting in a \'runtime error\' instead of \'time limit\'.',
                            'Fixed a bug that stuck any submission in \'C\', resulting in a \'runtime error\'.'
                        ]
                    ],
                    [
                        'version' => 'v1.0.1',
                        'title' => 'The Big Challenge! ⚔️💥🗡️🏆',
                        'date' => '09-07-2024',
                        'author' => 'Crazynds',
                        'features' => [
                            'Created fast problem import using ZIP files.',
                            'Created contest page!',
                            'Created contest mode and all contest related features! 🎉',
                            'Added C to the available languages!'
                        ],
                        'changes' => [
                            'FAQ and About options are no longer displayed while the pages do not exist.',
                            'The multipliers in python and pypy are now less strict.',
                            'Now we have a lot more result status to understand what happen with the submission.',
                            'Refactored all permissions levels.'
                        ],
                        'styles' => [
                            'Added a custom style to scroll bar.',
                            'Added \'Go Back\' buttons in some forgotten areas.',
                            'Increased area size of Home button.',
                            'Create a custom style para contest mode!',
                            'Problem layout improved.',
                            'Leaderboard layout improved.',
                            'Navbar layout improved.',
                            'Language multiplier table added to the submission form.'
                        ],
                        'fixes' => [
                            'Fixed crashes when downloading a problem with big inputs.',
                            'Fixed bugs when using teams with contest.',
                            'Now the server won\'t save the file if it\'s not a valid text file.',
                            'Fixed a bug when you couldn\'t download or see a old code.',
                            'Fixed a bug when you couldn\'t see why compilation error happens.'
                        ]
                    ],
                    [
                        'version' => 'v1.0.0',
                        'title' => 'Lets the party begin! 🎉🎉🎉',
                        'date' => '29-04-2024',
                        'author' => 'Crazynds',
                        'features' => [
                            'Added support for Python 3.11 and Pypy 3.10',
                            'Added party comemoration for accepted submissions',
                            'Added sadface rain for non accepted submissions',
                            'Created \'scorer\' to rank the submissions',
                            'Created a global rank',
                            'Created this update list 🥳'
                        ],
                        'changes' => [
                            'Enchanced Profile page and added a list of all solved problems by the user',
                            'Change submissions page to automatically update using sockets and broadcast messages'
                        ],
                        'styles' => [],
                        'fixes' => [
                            'Small bugs that I dont remember'
                        ]
                    ]
                ];
            @endphp

            @foreach($changelogs as $changelog)
                <div class="row">
                    <div class="col-8">
                        <h3>{{ $changelog['version'] }} - {{ $changelog['title'] }}</h3>
                        <p>
                            Date: {{ $changelog['date'] }}<br>
                            By: {{ $changelog['author'] }}
                        </p>
                        
                        @if(!empty($changelog['features']))
                            <p>Features ✨</p>
                            <ul>
                                @foreach($changelog['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if(!empty($changelog['changes']))
                            <p>Changes ⚙️</p>
                            <ul>
                                @foreach($changelog['changes'] as $change)
                                    <li>{{ $change }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if(!empty($changelog['styles']))
                            <p>Styles 🎨</p>
                            <ul>
                                @foreach($changelog['styles'] as $style)
                                    <li>{{ $style }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if(!empty($changelog['fixes']))
                            <p>Fixes 🐛</p>
                            <ul>
                                @foreach($changelog['fixes'] as $fix)
                                    <li>{{ $fix }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
                @if(!$loop->last)
                    <hr style="margin-top: 120px" />
                @endif
            @endforeach
        @endif
    @endauth
@endsection
