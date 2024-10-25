<div class="dashboard-sidebar">
    <div class="sidebar">
        <div class="nav d-flex flex-column">
            <ul>
                <div>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::currentRouteName() == 'dashboard' ? 'active' : '' }}"
                            aria-current="page" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                </div>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == 'dashboard.profile' ? 'active' : '' }}"
                        href="{{ route('dashboard.profile') }}">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == 'dashboard.enrolledCourses' ? 'active' : '' }}"
                        href="{{ route('dashboard.enrolledCourses') }}">
                        <i class="fas fa-book"></i> Enrolled Courses
                    </a>
                </li>
                <li class="nav-item {{ Route::currentRouteName() == 'dashboard.wishlist' ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('dashboard.wishlist') }}">
                        <i class="fas fa-heart"></i> Wishlist
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-question-circle"></i> Help Request
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-list"></i> View Enrollments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == 'dashboard.settings' ? 'active' : '' }}"
                        href="{{ route('dashboard.settings') }}">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
