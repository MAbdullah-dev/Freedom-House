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
                        <div class="status open">Open<div class="capacity">18<span>
                                    spots available
                                </span>
                            </div>
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
        </div>
    </section>
</div>



<script>
    document.querySelector('.filter-btn').addEventListener('click', function() {
        document.querySelector('.filter-row').classList.toggle('show');
    });
</script>
