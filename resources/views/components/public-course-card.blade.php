@props(['course'])

@php
    $detailsRoute = $course->course_type === 'recorded'
        ? route('onsite.course.details', $course->id)
        : route('course.details', $course->id);
    $rating = (int) round($course->average_rating ?? 0);
    $instructor = trim(($course->user->first_name ?? '').' '.($course->user->last_name ?? ''));
@endphp

<article class="card public-course-card h-100">
    <div class="card-img-top">
        @if ($course->thumbnail)
            <img src="{{ asset('storage/'.$course->thumbnail) }}" alt="{{ $course->title }}">
        @else
            <div class="public-course-card__placeholder" role="img" aria-label="Course image not available">
                Image not available
            </div>
        @endif
        {{ $bookmark ?? '' }}
    </div>
    <div class="card-body d-flex flex-column">
        <h3 class="card-title h5">{{ $course->title }}</h3>
        <div class="tutor-ratings d-flex align-items-center gap-1" aria-label="Rating {{ $rating }} out of 5">
            @for ($i = 1; $i <= 5; $i++)
                <i class="{{ $i <= $rating ? 'fa-solid' : 'fa-regular' }} fa-star" aria-hidden="true"></i>
            @endfor
        </div>
        @if ($instructor !== '')
            <p class="public-course-card__by mb-2">
                @if ($course->user && $course->user->pfp)
                    <img src="{{ asset('storage/'.$course->user->pfp) }}" alt="">
                @endif
                <span>{{ $instructor }}</span>
            </p>
        @endif
        <p class="card-text">{{ $course->description }}</p>
        <a class="button-primary mt-auto" href="{{ $detailsRoute }}">View course</a>
    </div>
</article>
