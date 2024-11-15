<div class="my-course px-5 w-100">
    <h2 class="py-4">Enrolled Courses</h2>
    <!-- Nav Tabs -->
    <ul class="nav nav-tabs" id="courseTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="enrolled-tab" data-bs-toggle="tab" href="#enrolled" role="tab"
                aria-controls="enrolled" aria-selected="true">
                Publish (2)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="active-tab" data-bs-toggle="tab" href="#active" role="tab"
                aria-controls="active" aria-selected="false">
                Pending (2)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="completed-tab" data-bs-toggle="tab" href="#completed" role="tab"
                aria-controls="completed" aria-selected="false">
                Draft (2)
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-4" id="courseTabsContent">
        <!-- Enrolled Courses Tab -->
        <div class="tab-pane fade show active" id="enrolled" role="tabpanel" aria-labelledby="enrolled-tab">
            <div class="enrolled-cards">
                <div class="row">
                    @foreach ($publishedCourses as $publishedCourse)
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <!-- Card 1 -->
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
                                            <a href="#" class="options-link">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </a>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Repeat this column for additional cards -->
                    {{-- <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <!-- Card 2 -->
                        <div class="card shadow">
                            <div class="card-img-top">
                                <img src="{{ asset('assets/images/featured-card-1.png') }}" alt="Course 1"
                                    class="img-fluid">
                            </div>
                            <div class="card-body">
                                <div class="course-date d-flex align-items-center gap-1">
                                    2024-06-24 10.56 am
                                </div>
                                <h5 class="card-title my-3">Documentation for Authorization</h5>
                                <div class="course-data d-flex align-items-center">
                                    <p>
                                        <i class="fa-regular fa-clock"></i><span>2h</span>
                                        <i class="fa-solid fa-user"></i><span>6</span>
                                    </p>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <p>Price: <span>Free</span></p>
                                <p>
                                    <span>
                                        <a href="#" class="edit-link">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a href="#" class="options-link">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </a>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <!-- Card 3 -->
                        <div class="card shadow">
                            <div class="card-img-top">
                                <img src="{{ asset('assets/images/featured-card-1.png') }}" alt="Course 1"
                                    class="img-fluid">
                            </div>
                            <div class="card-body">
                                <div class="course-date d-flex align-items-center gap-1">
                                    2024-06-24 10.56 am
                                </div>
                                <h5 class="card-title my-3">Documentation for Authorization</h5>
                                <div class="course-data d-flex align-items-center">
                                    <p>
                                        <i class="fa-regular fa-clock"></i><span>2h</span>
                                        <i class="fa-solid fa-user"></i><span>6</span>
                                    </p>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <p>Price: <span>Free</span></p>
                                <p>
                                    <span>
                                        <a href="#" class="edit-link">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a href="#" class="options-link">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </a>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
            {{-- <div class="card shadow">
                    <div class="card-img-top">
                        <img src="{{ asset('assets/images/featured-card-1.png') }}" alt="Course 1">
                    </div>
                    <div class="card-body">
                        <div class="course-date d-flex align-items-center gap-1">
                            2024-06-24 10.56 am
                        </div>
                        <h5 class="card-title my-3">Documentation for Authorization</h5>
                        <div class="course-data d-flex align-items-center">
                            <p><i class="fa-regular fa-clock"></i><span>2h</span><i
                                    class="fa-solid fa-user"></i><span>6</span></p>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <p>Price: <span>Free</span></p>
                        <p>
                            <span>
                                <a href=""><i class="fa-regular fa-pen-to-square"></i></a>
                                <a><i class="fa-solid fa-ellipsis-vertical"></i></a>
                            </span>
                        </p>
                    </div>
                </div> --}}
        </div>
    </div>

    <!-- Active Courses Tab -->
    <div class="tab-pane fade" id="active" role="tabpanel" aria-labelledby="active-tab">
        <p>Active courses content goes here.</p>
    </div>

    <!-- Completed Courses Tab -->
    <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
        <p>Completed courses content goes here.</p>
    </div>
</div>
</div>
