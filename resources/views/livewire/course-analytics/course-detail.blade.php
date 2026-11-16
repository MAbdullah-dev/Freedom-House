<section class="course-analytics px-4 py-4">
    @push('title')
        Course enrollment
    @endpush
    <a href="{{ route('dashboard.analytics') }}" class="back-link my-3 d-inline-block" wire:navigate>
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to analytics
    </a>
    <header class="dashboard-page-head">
        <h1>Course enrollment</h1>
        <p>Open a course from analytics to review its enrollment records.</p>
    </header>
    <p class="public-empty">No enrollment rows are loaded on this page.</p>
</section>
