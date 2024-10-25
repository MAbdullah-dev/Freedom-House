<div class="course-section px-5">
    <h2 class="py-4">Enrolled Courses</h2>
    <!-- Nav Tabs -->
    <ul class="nav nav-tabs" id="courseTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="enrolled-tab" data-bs-toggle="tab" href="#enrolled" role="tab"
                aria-controls="enrolled" aria-selected="true">
                Enrolled Courses (2)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="active-tab" data-bs-toggle="tab" href="#active" role="tab"
                aria-controls="active" aria-selected="false">
                Active Courses (2)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="completed-tab" data-bs-toggle="tab" href="#completed" role="tab"
                aria-controls="completed" aria-selected="false">
                Completed Courses (2)
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-4" id="courseTabsContent">
        <!-- Enrolled Courses Tab -->
        <div class="tab-pane fade show active" id="enrolled" role="tabpanel" aria-labelledby="enrolled-tab">
            <div class="enrolled-cards d-flex flex-wrap gap-4">
                <!-- Card 1 -->
                <div class="card shadow">
                    <div class="card-img-top">
                        <img src="{{ asset('assets/images/featured-card-1.png') }}" alt="Course 1">
                    </div>
                    <div class="card-body">
                        <div class="tutor-ratings d-flex align-items-center gap-1">
                            <i class="fa-regular fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                        </div>
                        <h5 class="card-title my-3">Documentation for Authorization</h5>
                        <div class="progress-info d-flex justify-content-between">
                            <p>0/0</p>
                            <p>0% Complete</p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-center">
                            <a href="#" class="button-primary mt-3">Start Learning</a>
                        </div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="card shadow">
                    <div class="card-img-top">
                        <img src="{{ asset('assets/images/featured-card-1.png') }}" alt="Course 1">
                    </div>
                    <div class="card-body">
                        <div class="tutor-ratings d-flex align-items-center gap-1">
                            <i class="fa-regular fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                            <i class="fa-regular fa-star"></i>
                        </div>
                        <h5 class="card-title my-3">Documentation for Authorization</h5>
                        <div class="progress-info d-flex justify-content-between">
                            <p>0/0</p>
                            <p>0% Complete</p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-center">
                            <a href="#" class="button-primary mt-3">Start Learning</a>
                        </div>
                    </div>
                </div>
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
