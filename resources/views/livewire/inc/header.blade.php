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
                <li><a href="{{ route('home') }}" wire:navigate>Home</a></li>
                <li><a href="{{ route('about-us') }}" wire:navigate>About</a></li>
                <li><a href="{{ route('course.catalogue') }}" wire:navigate>Course Catalog</a></li>
                <li><a href="" wire:navigate>Event</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" id="dropdownMenuLink" aria-haspopup="true"
                        aria-expanded="false">
                        Categories
                    </a>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                        <em class="d-block">Course Categories</em>
                        <div class="dropdown-grid">
                            <div>
                                <b>Mental Health</b>
                                <ul>
                                    <li><a class="" href="#">Trauma Informed Care</a></li>
                                    <li><a class="" href="#">Psychology</a></li>
                                    <li><a class="" href="#">Art Therapy</a></li>
                                </ul>
                            </div>
                            <div>
                                <b>Creativity</b>
                                <ul>
                                    <li><a class="" href="#">Book Writing</a></li>
                                    <li><a class="" href="#">Story Telling</a></li>
                                    <li><a class="" href="#">Novel Writing</a></li>
                                </ul>
                            </div>
                            <div>
                                <b>Stress Management</b>
                                <ul>
                                    <li><a class="" href="#">Anger Issue</a></li>
                                    <li><a class="" href="#">Coaching</a></li>
                                    <li><a class="" href="#">Sound Therapy</a></li>
                                    <li><a class="" href="#">Mindfulness</a></li>
                                </ul>
                            </div>
                            <div>
                                <b>Motivation</b>
                                <ul>
                                    <li><a class="" href="#">Goal Setting</a></li>
                                    <li><a class="" href="#">Habits</a></li>
                                    <li><a class="" href="#">Procrastination</a></li>
                                    <li><a class="" href="#">Time Management</a></li>
                                </ul>
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
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" id="dropdownMenuLinkMobile" aria-haspopup="true"
                    aria-expanded="false">
                    Categories
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuLinkMobile">
                    <em class="d-block">Course Categories</em>
                    <!-- Remaining dropdown content here -->
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
