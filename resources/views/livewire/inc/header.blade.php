<header>
    <div class="container-fluid nav-bar d-flex align-items-center justify-content-between">
        <a href="">
            <img src="{{ asset('assets/images/svgs/Group 1961.svg') }}" alt="">
        </a>
        <nav class="d-flex align-items-center ">
            <ul class="d-flex align-items-center gap-4">
                <li><a href="">Home</a></li>
                <li><a href="">About us</a></li>
                <li><a href="">Course Catalog</a></li>
                <li><a href="">Event</a></li>
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
        <div class="nav-btn">
            <a class="button-secondary" href="#">Login</a>
            <a class="button-primary" href="/register">Register</a>
        </div>
    </div>
</header>
@push('js')
    <script>
  document.addEventListener("DOMContentLoaded", function() {
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    dropdownToggle.addEventListener('click', function(e) {
        e.preventDefault();
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', function(e) {
        if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
            dropdownMenu.classList.remove('show');
        }
    });
});

    </script>
@endpush
