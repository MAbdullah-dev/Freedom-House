<div class="container mt-4">
    <!-- Course Name -->
    <h3 class="mb-4">{{ $course->title }}</h3>

    <!-- Lectures List -->
    <div class="card">
        <div class="card-body">
            <h5>Lectures</h5>
            <ul class="list-group">
                @forelse($lectures as $lecture)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $lecture->order }}. {{ $lecture->title }}</strong>
                                <p class="mb-0 text-muted">{{ $lecture->description }}</p>
                            </div>
                            <span class="badge bg-primary rounded-pill">
                                {{ gmdate('H:i:s', $lecture->video_duration) }}
                            </span>
                        </div>
                        <div>
                            <a class="btn btn-primary" href="">Update</a>
                            <a class="btn btn-danger" href="">Delete</a>
                            @if ($lecture->quizzes && !$lecture->quizzes->count())
                                <a class="btn btn-success"
                                    href="{{ route('quizzes.create', ['lectureId' => $lecture->id]) }}">
                                    Create Quiz
                                </a>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted">No lectures available.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
