<div class="tutor-profile">
    <section class="public-hero">
        <div class="container">
            <p class="public-hero__kicker">Tutor</p>
            <h1>{{ $tutor_name }}</h1>
            <p class="public-hero__lead">{{ $tutorCourses->total() }} {{ \Illuminate\Support\Str::plural('course', $tutorCourses->total()) }} on this profile.</p>
            @if ($tutor->user->pfp)
                <img class="tutor-profile__photo" src="{{ asset('storage/'.$tutor->user->pfp) }}" alt="{{ $tutor_name }}">
            @else
                <p>Photo not available.</p>
            @endif
        </div>
    </section>
    <section class="tutor-profile-content">
        <div class="container">
            <h2>Biography</h2>
            <p>{{ $tutor_bio ?: 'No biography has been added.' }}</p>
            <h2>Courses</h2>
            @if ($tutorCourses->count() === 0)
                <p class="public-empty">No courses are listed for this tutor yet.</p>
            @else
                <div class="row gy-4">
                    @foreach ($tutorCourses as $tutorCourse)
                        <div class="col-md-6 col-xl-4">
                            <x-public-course-card :course="$tutorCourse" />
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    {{ $tutorCourses->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
