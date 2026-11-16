<section class="student-view py-5 px-4">
    @push('title')
        Student
    @endpush
    <a href="{{ route('dashboard.students') }}" class="mb-4 d-block" wire:navigate>
        <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Back to students
    </a>

    @if ($studentdata === null)
        <p class="public-empty">No student record is available.</p>
    @else
        <header class="dashboard-page-head">
            <h1>{{ trim($studentdata->first_name.' '.$studentdata->last_name) }}</h1>
            <p>{{ $studentdata->email }}</p>
            <p>Registered {{ optional($studentdata->created_at)->toFormattedDateString() }}</p>
        </header>

        <h2 class="h4 mt-4">Enrolled courses</h2>
        @php
            $courses = $studentdata->bookings->pluck('course')->filter();
        @endphp
        @if ($courses->isEmpty())
            <p class="public-empty">This student has no enrolled courses.</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Course</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                            <tr>
                                <td>{{ $course->title }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</section>
