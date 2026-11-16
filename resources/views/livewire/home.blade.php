<div class="home">
    @push('title')
        Home
    @endpush
    <section class="public-hero">
        <div class="container">
            <p class="public-hero__kicker">Stellix Soft LMS</p>
            <h1>Software training, in one place</h1>
            <p class="public-hero__lead">Browse published courses, open a class calendar, and continue learning from recorded or scheduled sessions.</p>
            <div class="public-hero__actions">
                <a class="button-primary" href="{{ route('course.catalogue') }}" wire:navigate>Browse courses</a>
                <a class="button-secondary" href="{{ route('calendar') }}" wire:navigate>Class calendar</a>
                @guest
                    <a class="button-secondary" href="{{ route('register') }}" wire:navigate>Create an account</a>
                @endguest
            </div>
        </div>
    </section>

    <section class="featured-content">
        <div class="container">
            <h2>Published courses</h2>
            @if ($courses->isEmpty())
                <p class="public-empty">No published courses are available yet.</p>
            @else
                <div class="featured-cards swiper">
                    <div class="arrows d-flex align-items-center justify-content-end gap-2">
                        <button class="swiper-button-prev position-relative" type="button" aria-label="Previous courses"></button>
                        <button class="swiper-button-next position-relative" type="button" aria-label="Next courses"></button>
                    </div>
                    <div class="swiper-wrapper">
                        @foreach ($courses as $course)
                            <div class="swiper-slide">
                                <x-public-course-card :course="$course" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="public-points">
        <div class="container">
            <h2>How learning is organized</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="public-point">
                        <h3>Course catalog</h3>
                        <p>Search published courses and open the matching course page.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="public-point">
                        <h3>Class calendar</h3>
                        <p>See scheduled classes and events that are already on the calendar.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="public-point">
                        <h3>Recorded sessions</h3>
                        <p>Recorded courses open in the recorded learning view.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="news-letter">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 m-auto">
                    <div class="news-letter-content d-flex flex-column text-center">
                        <h2 class="mb-3">Course updates</h2>
                        <p>Leave an email address to hear about courses and events.</p>
                        <form wire:submit.prevent="submitEmail" class="d-flex flex-column gap-3">
                            <label class="visually-hidden" for="home-updates-email">Email address</label>
                            <input id="home-updates-email" type="email" wire:model="email" placeholder="Enter your email" required>
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <button type="submit" class="button-primary my-3">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@script
    <script type="text/javascript">
        if (document.querySelector('.featured-cards .swiper-slide')) {
            new Swiper('.featured-cards', {
                loop: false,
                grabCursor: true,
                spaceBetween: 24,
                navigation: {
                    nextEl: '.featured-cards .swiper-button-next',
                    prevEl: '.featured-cards .swiper-button-prev',
                },
                breakpoints: {
                    0: { slidesPerView: 1 },
                    768: { slidesPerView: 2 },
                    1024: { slidesPerView: 3 }
                }
            });
        }
    </script>
@endscript
