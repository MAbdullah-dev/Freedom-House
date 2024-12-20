<div class="CourseRequest py-5 px-4">
    <h2 class="mb-4">Course Requests</h2>
    <section class="tag-list mt-5">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course Title</th>
                        <th>Description</th>
                        <th>Created By</th>
                        <th>Approve</th>
                        <th>Reject</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($courses as $course)
                        <tr>
                            <td>{{ $course->id }}</td>
                            <td>{{ $course->title }}</td>
                            <td>{{ Str::limit($course->description, 50) }}</td>
                            <td>{{ $course->user->first_name }} {{ $course->user->last_name }}</td>
                            <td>
                                <button class="btn btn-outline-success" wire:click="approveCourse({{ $course->id }})">
                                    Approve
                                </button>
                            </td>
                            <td>
                                <button class="btn btn-outline-danger" wire:click="rejectCourse({{ $course->id }})">
                                    Reject
                                </button>
                            </td>
                            <td>
                                <a href="{{ route('course.details', $course->id) }}" target="_blank"
                                    class="btn btn-outline-warning">
                                    View Course
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
