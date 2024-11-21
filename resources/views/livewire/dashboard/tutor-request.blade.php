<div class="tutorRequest px-5">
    <h2>Course Requests</h2>

    <!-- Display flash messages -->
    @if (session()->has('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <section class="tag-list mt-5">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tutor Photo</th>
                        <th>Tutor Name</th>
                        <th>Specialization</th>
                        <th>Bio</th>
                        <th>Verified</th>
                        <th>CV</th>
                        <th>Approve</th>
                        <th>Reject</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tutors as $tutor)
                        <tr>
                            <td>{{ $tutor->id }}</td>
                            <td>
                                <img src="{{ asset('assets/images/dummy-profile-photo.png') }}" alt="Tutor Photo"
                                    style="width: 50px; height: 50px; border-radius: 50%;">
                            </td>
                            <td>{{ $tutor->user->first_name . ' ' . $tutor->user->last_name }}</td>
                            <td>{{ $tutor->specialization }}</td>
                            <td>{{ $tutor->user->bio }}</td>
                            <td>
                                <span
                                    class="badge text-white {{ $tutor->is_verified == 1 ? 'bg-success' : ($tutor->is_verified == 3 ? 'bg-danger' : 'bg-secondary') }}">
                                    {{ $tutor->is_verified == 1 ? 'Verified' : 'Unverified' }}
                                </span>
                            </td>
                            <td>
                                @if ($tutor->tutorFiles->isNotEmpty())
                                    <button wire:click="downloadFile('{{ $tutor->tutorFiles->first()->file_path }}')"
                                        class="btn btn-outline-primary">
                                        Download CV
                                    </button>
                                @else
                                    <span>No file uploaded</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-outline-success" wire:click="approveTutor({{ $tutor->id }})">
                                    Approve
                                </button>
                            </td>
                            <td>
                                <button class="btn btn-outline-danger" wire:click="rejectTutor({{ $tutor->id }})">
                                    Reject
                                </button>
                            </td>
                            <td>
                                <a href="{{ route('tutor.profile', $tutor->user_id) }}" target="_blank"
                                    class="btn btn-outline-warning">
                                    View Tutor
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
