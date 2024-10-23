<header>
    <div class="container nav-bar d-flex align-items-center justify-content-between">
        <a href="">
            <img src="{{ asset('assets/svgs/header_logo.svg') }}" alt="">
        </a>
        <div class="d-block d-xl-none" id="hamburger-icon">
            <i class="fa-solid fa-bars fa-xl" style="color: #104627;"></i>
        </div>
        <nav class="d-none d-xl-flex align-items-center">
            <ul class="d-flex align-items-center gap-3">
                <li><a class="{{ Route::currentRouteName() == 'home' ? 'active' : '' }}" href="{{ route('home') }}"
                        wire:navigate>Home</a></li>
                <li><a class="{{ Route::currentRouteName() == 'about-us' ? 'active' : '' }}"
                        href="{{ route('about-us') }}" wire:navigate>About</a></li>
                <li><a class="{{ Route::currentRouteName() == 'course.catalogue' ? 'active' : '' }}"
                        href="{{ route('course.catalogue') }}" wire:navigate>Course Catalog</a></li>
                <li><a class="{{ Route::currentRouteName() == '' ? 'active' : '' }}" href=""
                        wire:navigate>Event</a></li>
                <li class="category-dropdown ">
                    <a href="#">Category</a>
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
        </nav>
        <div class="nav-btn d-none d-xl-block">
            <a class="button-secondary" href="{{ route('login') }}" wire:navigate>Login</a>
            <a class="button-primary" href="{{ route('register') }}" wire:navigate>Register</a>
        </div>
    </div>
    <div class="mobile-menu d-xl-none d-flex align-items-center justify-content-center flex-column gap-1"
        id="mobile-nav">
        <ul class="align-items-center justify-content-center flex-column gap-2">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><a href="">About</a></li>
            <li><a href="">Course Catalog</a></li>
            <li><a href="">Event</a></li>
            <li class="category-dropdown ">
                <a href="#">Category</a>
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
        <div class="nav-btn mb-4 d-flex gap-2 flex-column">
            <a class="button-secondary" href="#">Login</a>
            <a class="button-primary" href="/register">Register</a>
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
