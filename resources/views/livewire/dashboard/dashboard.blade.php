<div class="dashboard px-4">
    <section class="courses-info-section">
        <div class="inner">
            <div class="courses-info">
                <h4 class="my-4">Dashboard</h4>
                <div class="course-detail d-flex align-items-center justify-content-center gap-3 flex-wrap">
                    <div class="course-box d-flex align-items-center justify-content-center flex-column">
                        <div class="course-box-icon">
                            <i class="fa-solid fa-book-open-reader"></i>
                        </div>
                        <div class="number">
                            3
                        </div>
                        <span>Enrolled Courses</span>
                    </div>
                    <div class="course-box d-flex align-items-center justify-content-center flex-column">
                        <div class="course-box-icon">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div class="number">
                            3
                        </div>
                        <span>Active Courses</span>
                    </div>
                    <div class="course-box d-flex align-items-center justify-content-center flex-column">
                        <div class="course-box-icon">
                            <i class="fa-solid fa-trophy">
                            </i>
                        </div>
                        <div class="number">
                            3
                        </div>
                        <span>Completed Courses</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="course-progress py-2">
        <h4 class="my-5">In Progress Course</h4>
        <div class="progress-wrapper py-2">
            <div class="inner">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="progress-course-img">
                            <img src="{{ asset('assets/images/progress-course-img.png') }}" alt="">
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="progress-course-detail gap-1">
                            <div class="progress-course-rating d-flex align-items-center my-3">
                                <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                <span>4.80 <span>(10.00)</span></span>
                            </div>
                            <h4>The Complete Web Development Bootcamp</h4>
                            <div class="lessons-complete my-1">completed <span> 0 of 0 Lessons</span> </div>
                            <div class="course-progress-bar d-flex align-items-center my-2 gap-1">
                                <progress id="file" value="32" max="100">32%</progress>
                                <p><span>0%</span>complete</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
