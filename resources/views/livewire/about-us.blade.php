<div class="about-us">
    @push('title')
        About
    @endpush
    <section class="public-hero">
        <div class="container">
            <p class="public-hero__kicker">Stellix Soft LMS</p>
            <h1>About this learning platform</h1>
            <p class="public-hero__lead">Stellix Soft LMS is the place to browse courses, meet the tutors who publish them, and open a class from the catalog.</p>
        </div>
    </section>
    <section class="about-us-body">
        <div class="container">
            <h2>What you can do here</h2>
            <p>Use the catalog to find a published course, the calendar to see scheduled classes, and your account to keep a wishlist. Tutor profiles show the name, biography, and courses stored for that tutor.</p>
        </div>
    </section>
    <section class="training-team-section">
        <div class="container">
            <h2>Tutors</h2>
            @if ($tutors->isEmpty())
                <p class="public-empty">No tutor profiles are available yet.</p>
            @else
                <div class="card-container">
                    @foreach ($tutors as $tutor)
                        <article class="card public-tutor-card">
                            <div class="card-img-top">
                                @if ($tutor->user->pfp)
                                    <img src="{{ asset('storage/'.$tutor->user->pfp) }}" alt="{{ $tutor->user->first_name }} {{ $tutor->user->last_name }}">
                                @else
                                    <div class="public-course-card__placeholder" role="img" aria-label="Tutor photo not available">Photo not available</div>
                                @endif
                            </div>
                            <div class="card-body">
                                <h3 class="h5">{{ $tutor->user->first_name }} {{ $tutor->user->last_name }}</h3>
                                @if ($tutor->specialization)
                                    <p class="public-tutor-card__role">{{ $tutor->specialization }}</p>
                                @endif
                                @if ($tutor->user->bio)
                                    <p>{{ $tutor->user->bio }}</p>
                                @endif
                                <a href="{{ route('tutor.profile', $tutor->user_id) }}" wire:navigate>View profile</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
