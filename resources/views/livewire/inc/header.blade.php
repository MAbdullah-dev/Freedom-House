<header>
    <div class="container-fluid nav-bar d-flex align-items-center justify-content-between">
        <a href="">
            <img src="{{ asset('assets/svgs/header_logo.svg') }}" alt="">
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
                                    <li><a class="dropdown-item" href="#">Trauma Informed Care</a></li>
                                    <li><a class="dropdown-item" href="#">Psychology</a></li>
                                    <li><a class="dropdown-item" href="#">Art Therapy</a></li>
                                </ul>
                            </div>
                            <div>
                                <b>Creativity</b>
                                <ul>
                                    <li><a class="dropdown-item" href="#">Book Writing</a></li>
                                    <li><a class="dropdown-item" href="#">Story Telling</a></li>
                                    <li><a class="dropdown-item" href="#">Novel Writing</a></li>
                                </ul>
                            </div>
                            <div>
                                <b>Creativity</b>
                                <ul>
                                    <li><a class="dropdown-item" href="#">Book Writing</a></li>
                                    <li><a class="dropdown-item" href="#">Story Telling</a></li>
                                    <li><a class="dropdown-item" href="#">Novel Writing</a></li>
                                </ul>
                            </div>
                            <div>
                                <b>Stress Management</b>
                                <ul>
                                    <li><a class="dropdown-item" href="#">Anger Issue</a></li>
                                    <li><a class="dropdown-item" href="#">Coaching</a></li>
                                    <li><a class="dropdown-item" href="#">Sound Therapy</a></li>
                                    <li><a class="dropdown-item" href="#">Mindfulness</a></li>
                                </ul>
                            </div>
                            <div>
                                <b>Motivation</b>
                                <ul>
                                    <li><a class="dropdown-item" href="#">Goal Setting</a></li>
                                    <li><a class="dropdown-item" href="#">Habits</a></li>
                                    <li><a class="dropdown-item" href="#">Procrastination</a></li>
                                    <li><a class="dropdown-item" href="#">Time Management</a></li>
                                </ul>
                            </div>

                            <div>
                                <b>Motivation</b>
                                <ul>
                                    <li><a class="dropdown-item" href="#">Goal Setting</a></li>
                                    <li><a class="dropdown-item" href="#">Habits</a></li>
                                    <li><a class="dropdown-item" href="#">Procrastination</a></li>
                                    <li><a class="dropdown-item" href="#">Time Management</a></li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </li>
            </ul>
        </nav>
        <div class="nav-btn">
            <a class="button-secondary" href="#">Login</a>
            <a class="button-primary" href="#">Register</a>
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
