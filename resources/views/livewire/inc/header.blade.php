<?php
// dd(Auth::check());
?>
<header>
    <div class="container nav-bar d-flex align-items-center justify-content-between">
        <a href="">
            <img class="site-logo" src="{{ asset('assets/svgs/header_logo.svg') }}" alt="">
        </a>
        <div class="d-block d-xl-none" id="hamburger-icon">
            <i class="fa-solid fa-bars-staggered fa-xl"></i>
        </div>
        <nav class="d-none d-xl-flex align-items-center">
            <ul class="d-flex align-items-center gap-3">
                <li><a class="{{ Route::currentRouteName() == 'home' ? 'active' : '' }}" href="{{ route('home') }}"
                        wire:navigate>Home</a></li>
                <li><a class="{{ Route::currentRouteName() == 'about-us' ? 'active' : '' }}"
                        href="{{ route('about-us') }}" wire:navigate>About</a></li>
                <li><a class="{{ Route::currentRouteName() == 'course.catalogue' ? 'active' : '' }}"
                        href="{{ route('course.catalogue') }}" wire:navigate>Course Catalog</a></li>
                <li><a class="{{ Route::currentRouteName() == 'calendar' ? 'active' : '' }}"
                        href="{{ route('calendar') }}" wire:navigate>Class
                        Calendar</a></li>
                <li class="category-dropdown ">
                    <a href="#">Category <i class="fa-solid fa-angle-down fa-xs"></i></a>
                    <div class="menu-dropdown">
                        <h4>Course Categories</h4>
                        <hr>
                        <div class="category-grid">
                            <div class="row">
                                @foreach ($categories as $category)
                                    <div class="col-lg-6">
                                        <div>
                                            <b>{{ $category->category_name }}</b>
                                            <ul>
                                                @foreach ($category->courses as $course)
                                                    @if ($uniqueCourses->contains('id', $course->id))
                                                        <li>
                                                            @if ($course->course_type == 'recorded')
                                                                <a
                                                                    href="{{ route('onsite.course.details', $course->id) }}">{{ $course->title }}</a>
                                                            @else
                                                                <a
                                                                    href="{{ route('course.details', $course->id) }}">{{ $course->title }}</a>
                                                            @endif
                                                        </li>
                                                        @php
                                                            $uniqueCourses = $uniqueCourses->reject(function ($c) use (
                                                                $course,
                                                            ) {
                                                                return $c->id === $course->id;
                                                            });
                                                        @endphp
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>
        <div class="nav-btn d-flex align-items-center gap-2 mx-2">
            <div class="user-login">
                @if (Auth::check())
                    <div class="login-icon">
                        <img class="login-icon-img"
                            src="{{ asset('storage/' . Auth::user()->pfp) ?? asset('assets/images/dummy-profile-photo.png') }}"
                            alt="">
                    </div>
                    <div class="user-dropdown">
                        <span class="fs-5 mb-3 d-block">{{ Auth::user()->first_name }}</span>
                        <ul>
                            <li>
                                <a href="{{ route('dashboard') }}" wire:navigate>
                                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('dashboard.settings') }}" wire:navigate>
                                    <i class="fa-solid fa-user"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('dashboard.wishlist') }}" wire:navigate>
                                    <i class="fa-solid fa-heart"></i> Wishlist
                                </a>
                            </li>
                        </ul>
                        <hr>
                        <ul>
                            <li>
                                <a href="{{ route('dashboard.settings') }}" wire:navigate>
                                    <i class="fa-solid fa-gear"></i> Settings
                                </a>
                            </li>
                            <li>
                                <a href="#" wire:click="logout">
                                    <i class="fa-solid fa-right-from-bracket cursor-pointer"></i> Log Out
                                </a>
                            </li>
                        </ul>
                    </div>

            </div>
        @else
            <div class="nav-btn d-flex gap-2">
                <a class="button-secondary" href="{{ route('login') }}" wire:navigate>Login</a>
                <a class="button-primary" href="{{ route('register') }}" wire:navigate>Register</a>
            </div>
            @endif
        </div>
    </div>
    </div>
    <div class="mobile-menu align-items-start justify-content-start d-xl-none d-flex flex-column gap-1 px-2"
        id="mobile-nav">
        <ul class="align-items-center justify-content-center flex-column gap-3">
            <li><a class="{{ Route::currentRouteName() == 'home' ? 'active' : '' }}" href="{{ route('home') }}"
                    wire:navigate>Home</a></li>
            <li><a class="{{ Route::currentRouteName() == 'about-us' ? 'active' : '' }}"
                    href="{{ route('about-us') }}" wire:navigate>About</a></li>
            <li><a class="{{ Route::currentRouteName() == 'course.catalogue' ? 'active' : '' }}"
                    href="{{ route('course.catalogue') }}" wire:navigate>Course Catalog</a></li>
            <li><a class="{{ Route::currentRouteName() == 'calendar' ? 'active' : '' }}"
                    href="{{ route('calendar') }}" wire:navigate>Class
                    Calendar</a></li>
            <li class="category-dropdown ">
                <a href="#">Category <i class="fa-solid fa-angle-down fa-xs"></i></a>
                <div class="menu-dropdown">
                    <h4>Course Categories</h4>
                    <hr>
                    <div class="category-grid">
                        <div class="row">
                            <div class="col-lg-6">
                                <div>
                                    <b>Mental Health</b>
                                    <ul>
                                        <li><a href="#">- Adult Mental Health First Aid</a></li>
                                        <li><a href="#">- Youth Mental Health First Aid</a></li>
                                        <li><a href="#">- Question. Persuade. Refer.</a></li>
                                        <li><a href="#">- Mindset</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <b>Substance Use Disorder</b>
                                    <ul>
                                        <li><a href="#">- Recovery-Oriented System of Care</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div>
                                    <b>Ethics</b>
                                    <ul>
                                        <li><a href="#">- Maintaining Professional Boundaries</a></li>
                                        <li><a href="#">- Clinical Documentation</a></li>
                                        <li><a href="#">- QI Orientation</a></li>
                                        <li><a href="#">- Documentation for Authorization</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
        <div class="nav-btn mb-4 d-flex gap-2">
            @if (Auth::check())
                <button class="button-primary">Logout</button>
            @else
                <a class="text-danger" href="{{ asset('/login') }}">Login</a>
                <a class="button-primary" href="{{ asset('/register') }}">Register</a>
            @endif
        </div>

    </div>
</header>

@push('js')
    <script>
        $(document).ready(function() {
            $('#hamburger-icon').on('click', function() {
                $('#mobile-nav').toggleClass('show');
            });
        });
    </script>
@endpush
