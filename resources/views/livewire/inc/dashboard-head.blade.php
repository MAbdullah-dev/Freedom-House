<div class="dashboard-head">
    <div class="container">
        <div class="inner d-flex align-items-center justify-content-between tutor-data flex-wrap pb-2">
            <div class="tutor-info">
                <ul class="tutor-avatar d-flex align-items-center gap-3">
                    <li><img src="{{ asset('assets/images/user.png') }}" alt="User"></li>
                    <li>
                        <span>Hello</span>
                        <b class="d-block">{{ $username }}</b>
                    </li>
                </ul>
            </div>
            <div class="tutor-notification-request d-flex gap-2">
                <button class="offcanvas-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight"
                    aria-controls="offcanvasRight">
                    <i class="fa-solid fa-bell"></i>
                </button>

                @php
                    $roleIds = auth()->user()->roles->pluck('id')->toArray();
                    $isTutorVerified = auth()->user()->tutor->is_verified ?? null;
                @endphp

                @if (in_array(2, $roleIds))
                    @if ($isTutorVerified)
                        <button type="button" class="button-secondary"
                            onclick="location.href='{{ route('dashboard.create.course') }}'">
                            Create Course
                        </button>
                    @else
                        <p class="text-danger mt-2">Your request to become a tutor is pending.</p>
                    @endif
                @elseif (in_array(3, $roleIds))
                    <button type="button" class="button-secondary" data-bs-toggle="modal"
                        data-bs-target="#instructorModal">
                        Become an Instructor
                    </button>
                @endif
            </div>
        </div>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
            <div class="offcanvas-header">
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                .....
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="instructorModal" tabindex="-1" aria-labelledby="instructorModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="instructorModalLabel">Become an Instructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="createTutor">
                        <div class="mb-3">
                            <label for="specialization" class="form-label">Specialization</label>
                            <input type="text" wire:model="specialization" class="form-control" id="specialization">
                        </div>

                        <div class="mb-3">
                            <label for="preferred_teaching_method" class="form-label">Preferred Teaching Method</label>
                            <select wire:model="preferred_teaching_method" class="form-control">
                                <option value="">Select</option>
                                <option value="online">Online</option>
                                <option value="in-person">In-Person</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="experience" class="form-label">Experience</label>
                            <textarea wire:model="experience" class="form-control" id="experience"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="file_path" class="form-label">Attach Your CV</label>
                            <input type="file" wire:model="file_path" class="form-control">
                        </div>

                        <button type="submit" class="btn button-primary w-100">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        window.addEventListener('close-modal', () => {
            $('#instructorModal').modal('hide');
        });
    </script>
@endscript
