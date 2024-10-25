<div class="dashboard-sidebar">
    <div class="sidebar">
        <div class="nav d-flex flex-column">
            <ul>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == 'dashboard' ? 'active' : '' }}" aria-current="page" href="{{ route('dashboard') }}" wire:navigate>
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == '' ? 'active' : '' }}" href="#" wire:navigate>
                        <i class="fas fa-user"></i> My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == '' ? 'active' : '' }}" href="#" wire:navigate>
                        <i class="fas fa-book"></i> Enrolled Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == '' ? 'active' : '' }}" href="#" wire:navigate>
                        <i class="fas fa-heart"></i> Wishlist
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == 'dashboard.help' ? 'active' : '' }}" href="{{ route('dashboard.help') }}" wire:navigate>
                        <i class="fas fa-question-circle"></i> Help Request
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == 'dashboard.enrollments' ? 'active' : '' }}" href="{{ route('dashboard.enrollments') }}" wire:navigate>
                        <i class="fas fa-list"></i> View Enrollments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::currentRouteName() == '' ? 'active' : '' }}" href="#" wire:navigate>
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
