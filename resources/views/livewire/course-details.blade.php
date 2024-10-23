<div class="course-details">
    <section class="hero" style="background-image: url('{{ asset('assets/images/course.png') }}');">
        <div class="container">
            <div class="inner">
                <div class="hero-details">
                    <p class="course-heading-border">Course Description</p>
                    <h1>Question. Persuade. Refer.</h1>
                    <p>
                        <strong>QPR or Question, Persuade and Refer</strong> is a training focused on suicide prevention
                        techniques and
                        conversation strategies. This training also provides resources to help yourself and others.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="course-classes">
        <div class="container">
            <h2>Available Classes</h2>

            <div class="search-container">
                <div class="search-bar">
                    <img src="{{ asset('assets/svgs/search-icon.svg') }}" alt="Search icon" />
                    <input type="text" placeholder="Search classes by keyword" />
                </div>

                <button class="filter-btn">
                    <img src="{{ asset('assets/svgs/filter-icon.svg') }}" alt="Filter icon" /> Filters
                </button>
            </div>

            <div class="filter-row">
                <select class="location-dropdown mb-3" id="location">
                    <option value="">Select location</option>
                    <option value="online">Online</option>
                    <option value="onsite">On-site</option>
                </select>
                <input type="text" class="calendar-input mb-3" id="calendar" placeholder="Select date" />
            </div>

            <div class="event-card">
                <div class="event-card__date">
                    <div class="day">12</div>
                    <div class="month">Oct</div>
                    <div class="time">2:00pm - 3:30pm</div>
                </div>
                <div class="event-card__info mx-4">
                    <div class="event-name">Online QPR Training</div>
                    <div class="event-card__details d-flex">
                        <div class="status open me-2">Open</div>
                        <div class="capacity">18<span>
                                spots available
                            </span>
                        </div>
                    </div>
                </div>
                <div class="event-card__actions">
                    <div class="price">FREE</div>
                    <button class="enroll-btn">Enroll Now</button>
                </div>
            </div>
        </div>
    </section>

    <section class="course-video">
        <div class="container">
            <div class="video">
                <iframe src="https://www.youtube.com/embed/some-video-id" frameborder="0" allowfullscreen></iframe>
            </div>
            <div class="course-details-tabs mt-5">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="most-popular-tab" data-bs-toggle="tab" href="#most-popular"
                            role="tab" aria-controls="most-popular" aria-selected="true">Course Info</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="new-tab" data-bs-toggle="tab" href="#new" role="tab"
                            aria-controls="new" aria-selected="false" tabindex="-1">Reviews</a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active text-start" id="most-popular" role="tabpanel"
                        aria-labelledby="most-popular-tab">
                        <div class="course-details__content">
                            <h3 class="mt-3">About Course</h3>
                            <p><strong>QPR or Question, Persuade and Refer</strong> is a training focused on suicide
                                prevention techniques and conversation strategies. This training also provides resources
                                to help yourself and others. You will learn common causes of suicidal behavior, warning
                                signs of suicide, and how to get help for someone in crisis. The training is
                                one hour and certification is for up to 2 years.</p>
                        </div>
                        <div class="course-details__widget">
                            <h3>What Will You Learn?</h3>
                            <ul>
                                <li>QPR or Question, Persuade and Refer is a training focused on suicide prevention
                                    techniques and conversation strategies.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="new" role="tabpanel" aria-labelledby="new-tab">
                        <div>
                            <h3 class="text-left d-flex my-4 fs-5">Student Ratings & Reviews</h3>
                            <div class="d-flex flex-column align-items-center ">
                                {{-- <img src="{{ asset('assets/svgs/emptystate.svg') }}" alt=""> --}}
                                <span class="text-secondary">No Review Yet</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="news-letter">
        <div class="row">
            <div class="col-lg-7 m-auto">
                <div class="news-letter-content d-flex flex-column text-center">
                    <h6 class="mb-3">Join our list to learn more</h6>
                    <p>Sign up to get updates on courses and events.</p>
                    <form action="" class="d-flex flex-column gap-3">
                        <input type="text" placeholder="Enter Your Email">
                        <button type="submit" class="button-primary my-3">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>



<script>
    document.querySelector('.filter-btn').addEventListener('click', function() {
        document.querySelector('.filter-row').classList.toggle('show');
    });
</script>
