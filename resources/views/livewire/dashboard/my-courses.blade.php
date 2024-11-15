<div class="my-course px-5 w-100 ">
    <h2 class="py-4">Enrolled Courses</h2>
    <!-- Nav Tabs -->
    <ul class="nav nav-tabs" id="courseTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="enrolled-tab" data-bs-toggle="tab" href="#enrolled" role="tab"
                aria-controls="enrolled" aria-selected="true">
                Publish ({{ $publishedCourses->count() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="active-tab" data-bs-toggle="tab" href="#active" role="tab"
                aria-controls="active" aria-selected="false">
                Pending ({{ $pendingCourses->count() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="completed-tab" data-bs-toggle="tab" href="#completed" role="tab"
                aria-controls="completed" aria-selected="false">
                Draft ({{ $draftedCourses->count() }})
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-4" id="courseTabsContent">
        <!-- Publish Tab -->
        <div class="tab-pane fade show active" id="enrolled" role="tabpanel" aria-labelledby="enrolled-tab">
            <div class="publish-cards">
                <div class="row">
                    @foreach ($publishedCourses as $publishedCourse)
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-img-top">
                                    <img src="{{ asset('assets/images/featured-card-1.png') }}" alt="Course 1"
                                        class="img-fluid">
                                </div>
                                <div class="card-body">
                                    <div class="course-date d-flex align-items-center gap-1">
                                        2024-06-24 10.56 am
                                    </div>
                                    <h5 class="card-title my-3">{{ $publishedCourse->title }}</h5>
                                    <div class="course-data d-flex align-items-center">
                                        <p>
                                            <i class="fa-regular fa-clock"></i><span>2h</span>
                                            <i class="fa-solid fa-user"></i><span>6</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <p>Price: <span>Free</span></p>
                                    <p>
                                        <span>
                                            <a href="#" class="edit-link">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <a href="#" class="delete-link" data-bs-toggle="modal"
                                                data-bs-target="#deleteCourseModal"
                                                wire:click.prevent="confirmDelete({{ $publishedCourse->id }})">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </a>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Pending Tab -->
        <div class="tab-pane fade" id="active" role="tabpanel" aria-labelledby="active-tab">
            <div class="pending-card">
                <div class="row">
                    @foreach ($pendingCourses as $pendingCourse)
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-img-top">
                                    <img src="{{ asset('assets/images/featured-card-1.png') }}" alt="Course 1"
                                        class="img-fluid">
                                </div>
                                <div class="card-body">
                                    <div class="course-date d-flex align-items-center gap-1">
                                        2024-06-24 10.56 am
                                    </div>
                                    <h5 class="card-title my-3">{{ $pendingCourse->title }}</h5>
                                    <div class="course-data d-flex align-items-center">
                                        <p>
                                            <i class="fa-regular fa-clock"></i><span>2h</span>
                                            <i class="fa-solid fa-user"></i><span>6</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <p>Price: <span>Free</span></p>
                                    <p>
                                        <span>
                                            <a href="#" class="edit-link">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <a href="#" class="delete-link" data-bs-toggle="modal"
                                                data-bs-target="#deleteCourseModal"
                                                wire:click.prevent="confirmDelete({{ $pendingCourse->id }})">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </a>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Draft Tab -->
        <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
            <div class="draft-cards">
                <div class="row">
                    @foreach ($draftedCourses as $draftedCourse)
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-img-top">
                                    <img src="{{ asset('assets/images/featured-card-1.png') }}" alt="Course 1"
                                        class="img-fluid">
                                </div>
                                <div class="card-body">
                                    <div class="course-date d-flex align-items-center gap-1">
                                        2024-06-24 10.56 am
                                    </div>
                                    <h5 class="card-title my-3">{{ $draftedCourse->title }}</h5>
                                    <div class="course-data d-flex align-items-center">
                                        <p>
                                            <i class="fa-regular fa-clock"></i><span>2h</span>
                                            <i class="fa-solid fa-user"></i><span>6</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <p>Price: <span>Free</span></p>
                                    <p>
                                        <span>
                                            <a href="#" class="edit-link">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <a href="#" class="delete-link" data-bs-toggle="modal"
                                                data-bs-target="#deleteCourseModal"
                                                wire:click.prevent="confirmDelete({{ $draftedCourse->id }})">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </a>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <!-- Delete Modal -->
    <div wire:ignore.self class="modal fade" id="deleteCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="destroyCourse">
                    <div class="modal-body">
                        Are you sure you want to delete this tag?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button data-bs-dismiss="modal" type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('js')
    <script>
        window.addEventListener('close-modal', event => {
            $('#deleteCourseModal').modal('hide');
        });
    </script>
@endpush
