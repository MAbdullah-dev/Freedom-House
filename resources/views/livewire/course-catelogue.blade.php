<style>
    .course-catelogue-main {
        background-image: url('{{ asset('assets/images/course.png') }}');
    }
</style>
<div class="course-catelogue">
    <section class="course-catelogue-main">
        <div class="container d-flex flex-column justify-content-center align-items-center">
            <h3>PROGRAMS WE OFFER</h3>
            <h1 class="my-5">ALL COURSES</h1>
        </div>
    </section>
    <section class="course-catelogue-list">
        <div class="container mt-5">
            <div x-data="{ tab: 'mostPopular' }" class="container mt-5">

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link" :class="{ 'active': tab === 'mostPopular' }"
                            @click="tab = 'mostPopular'">Most Popular</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ 'active': tab === 'new' }" @click="tab = 'new'">New</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ 'active': tab === 'trending' }"
                            @click="tab = 'trending'">Trending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ 'active': tab === 'onsite' }" @click="tab = 'onsite'">Onsite</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ 'active': tab === 'virtual' }"
                            @click="tab = 'virtual'">Virtual</a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-4">
                    <!-- Most Popular Tab -->
                    <div x-show="tab === 'mostPopular'">
                        <livewire:courses-cards.most-papular-cards />
                    </div>
                    <!-- New Tab -->
                    <div x-show="tab === 'new'">
                        <livewire:courses-cards.new-cards />
                    </div>
                    <!-- Trending Tab -->
                    <div x-show="tab === 'trending'">
                        <livewire:courses-cards.trending-cards />
                    </div>
                    <!-- Onsite Tab -->
                    <div x-show="tab === 'onsite'">
                        <livewire:courses-cards.onsite-cards />
                    </div>
                    <!-- Virtual Tab -->
                    <div x-show="tab === 'virtual'">
                        <livewire:courses-cards.virtual-cards />
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="course-catelogue">
        <div class="container">
            <h4>Category</h4>
            <button class=" d-xxl-none d-lg-block ms-auto offcanvas-filter" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                <i class="fa-solid fa-sliders"></i>
            </button>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <!-- Search Input -->
                <div class="input-group w-50 position-relative filter-search">
                    <div class="input-group-prepend">
                        <span class="input-group-text position-absolute" id="search-icon">
                            <i class="fas fa-search"></i> <!-- FontAwesome icon for search -->
                        </span>
                    </div>
                    <input type="text" class="form-control" placeholder="Search" aria-label="Search" aria-describedby="search-icon">
                </div>

                <!-- Sort Dropdown -->
                <div class="dropdown">

                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Course Title (z-a)
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="#">Course Title (a-z)</a>
                        <a class="dropdown-item" href="#">Course Title (z-a)</a>
                        <a class="dropdown-item" href="#">Date (Newest)</a>
                        <a class="dropdown-item" href="#">Date (Oldest)</a>
                    </div>
                </div>
            </div>

            <!-- Offcanvas component -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
                <div class="offcanvas-header">
                    <h5 id="offcanvasRightLabel">Offcanvas Right</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body">
                    <div class="filter">
                        <ul class="my-4">
                            <h4>Category</h4>
                            <li>
                                <input type="checkbox" id="ethics">
                                <label for="ethics">Ethics</label>
                            </li>
                            <li>
                                <input type="checkbox" id="mental-health">
                                <label for="mental-health">Mental Health</label>
                            </li>
                            <li>
                                <input type="checkbox" id="substance-use">
                                <label for="substance-use">Substance Use Disorder</label>
                            </li>
                        </ul>
                        <ul class="my-4">
                            <h4>Course Type</h4>
                            <li>
                                <input type="checkbox" id="on-site">
                                <label for="on-site">Onsite/In Person</label>
                            </li>
                            <li>
                                <input type="checkbox" id="virtual">
                                <label for="virtual">Virtual</label>
                            </li>
                        </ul>
                        <ul class="list-unstyled my-4">
                            <h4>Price</h4>
                            <li>
                                <input type="checkbox" id="free">
                                <label for="free">Free</label>
                            </li>
                            <li>
                                <input type="checkbox" id="paid">
                                <label for="paid">Paid</label>
                            </li>
                        </ul>
                        <button class="btn filter-btn" @click="clearFilters()"><i class="fa-solid fa-xmark"></i> Clear All Filters</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 d-xxl-block d-lg-none">
                    <div class="filter">
                        <ul class="my-4">
                            <h4>Category</h4>
                            <li>
                                <input type="checkbox" id="ethics">
                                <label for="ethics">Ethics</label>
                            </li>
                            <li>
                                <input type="checkbox" id="mental-health">
                                <label for="mental-health">Mental Health</label>
                            </li>
                            <li>
                                <input type="checkbox" id="substance-use">
                                <label for="substance-use">Substance Use Disorder</label>
                            </li>
                        </ul>
                        <ul class="my-4">
                            <h4>Course Type</h4>
                            <li>
                                <input type="checkbox" id="on-site">
                                <label for="on-site">Onsite/In Person</label>
                            </li>
                            <li>
                                <input type="checkbox" id="virtual">
                                <label for="virtual">Virtual</label>
                            </li>
                        </ul>
                        <ul class="list-unstyled my-4">
                            <h4>Price</h4>
                            <li>
                                <input type="checkbox" id="free">
                                <label for="free">Free</label>
                            </li>
                            <li>
                                <input type="checkbox" id="paid">
                                <label for="paid">Paid</label>
                            </li>
                        </ul>
                        <button class="btn filter-btn" @click="clearFilters()"><i class="fa-solid fa-xmark"></i> Clear All Filters</button>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="row gy-4">
                        <!-- Repeat card structure for course cards -->
                        <div class="col-md-6 col-lg-4">
                            <div class="card shadow">
                                <div class="card-img-top">
                                    <img src="{{ asset('assets/images/featured-card-1.png') }}" class="card-img-top" alt="...">
                                    <i class="fa-regular fa-bookmark"></i>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">Recovery-Oriented System of Care</h5>
                                    <div class="tutor-ratings d-flex align-items-center gap-1">
                                        <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                        <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                        <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                        <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                        <i class="fa-regular fa-star" style="color: #eea015;"></i>
                                    </div>
                                    <div class="tutor-meta-wrapper d-flex align-items-center gap-5">
                                        <div class="tutor-data d-flex align-items-center gap-1">
                                            <img src="{{ asset('assets/images/created_by.png') }}" alt="">
                                            <small>by</small><span>Autumn Green</span>
                                        </div>
                                        <div class="tutor-meta-icon d-flex align-items-center gap-2">
                                            <div class="meta-icon d-flex align-items-center">
                                                <i class="fa-regular fa-user" style="color: #757c8e;"></i>
                                                <span>11</span>
                                            </div>
                                            <div class="meta-icon d-flex align-items-center">
                                                <i class="fa-regular fa-clock" style="color: #757c8e;"></i>
                                                <span>2h</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="card-text">A recovery-oriented system of care is a network of clinical and nonclinical use recovery. While co…</p>
                                    <div class="post-details">
                                        <center>
                                            <span class="duration">2hr &nbsp;|&nbsp;</span>
                                            <span class="date">Publish date: May 24 2024</span>
                                        </center>
                                    </div>
                                    <a class="button-primary mt-3 d-block w-100 text-center" href="#">More Details...</a>
                                </div>
                            </div>
                        </div>
                        <!-- Repeat card structure for additional course cards -->
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

</div>
@script
    <script type="text/javascript">
        const swiper = new Swiper('.swiper', {
            loop: true,
            grabCursor: true,
            // spaceBetween: 30,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                // when window width is >= 320px
                320: {
                    slidesPerView: 1,
                    spaceBetween: 0,
                },
                // when window width is >= 640px
                640: {
                    slidesPerView: 2,
                    spaceBetween: 0,
                },
                // when window width is >= 1024px
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 0,
                },
                // when window width is >= 1440px
                1440: {
                    slidesPerView: 4,
                    spaceBetween: 0,
                },
            }
        });
    </script>
@endscript
