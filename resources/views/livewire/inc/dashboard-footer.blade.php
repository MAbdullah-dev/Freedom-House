<div class="dashboard-footer-mobile">

    <div class="tutor-dashboard-footer-mobile">
        <div class="tutor-container px-3">
            <div class="row d-flex align-items-center text-center mx-3">
                @if (Auth::user()->hasAnyRoleName(\App\Models\Role::TUTOR, \App\Models\Role::ADMIN))
                    <a class="col-4 d-flex flex-column text-secondary" href="{{ route('dashboard.mycourses') }}" wire:navigate>
                        <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                        <span>My Courses</span>
                    </a>
                @else
                    <a class="col-4 d-flex flex-column text-secondary" href="{{ route('dashboard.enrolledCourses') }}" wire:navigate>
                        <i class="fas fa-book" aria-hidden="true"></i>
                        <span>Courses</span>
                    </a>
                @endif
                <a class="col-4 d-flex flex-column text-secondary" href="{{ route('dashboard.help') }}" wire:navigate>
                    <i class="fa-solid fa-person-circle-question" aria-hidden="true"></i>
                    <span>Help</span>
                </a>
                <button class="col-4 d-flex flex-column text-secondary border-0 bg-transparent" id="hamburger-icon-footer"
                    type="button" aria-controls="dashboard-sidebar" aria-expanded="false">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                    <span>Menu</span>
                </button>
            </div>
        </div>
    </div>
</div>
@script
@push('js')
<script>
$(document).off('click.stellixSidebar', '#hamburger-icon-footer, #dashboard-sidebar-close');
$(document).on('click.stellixSidebar', '#hamburger-icon-footer, #dashboard-sidebar-close', function(event) {
    event.preventDefault();
    var sidebar = $('#dashboard-sidebar');
    var open = !sidebar.hasClass('visible');
    sidebar.toggleClass('visible', open);
    $('#hamburger-icon-footer').attr('aria-expanded', open ? 'true' : 'false');
});
</script>
@endpush
@endscript
