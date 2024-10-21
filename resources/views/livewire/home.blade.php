<div class="home">
    <section class="hero-section" style="background-image: url('{{ asset('assets/images/home-hero-img.png') }}');">
        <div class="container d-flex flex-column justify-content-center align-items-center">
            <h3>FREEDOM HOUSE TRAINING SYSYTEM</h3>
            <h1 class="my-5">FREEDOM HOUSE UNIVERSITY</h1>
            <div class="hero-cta-btn d-flex mt-2 gap-2 flex-wrap">
                <a href="#">Upcoming TRAINING</a>
                <a href="#">REGISTRATION</a>
                <a href="#">RESOURCE LIBRARY</a>
            </div>
        </div>
    </section>
    <section class="featured-content">
        <div class="container">
            <h2><span>FEATURED</span> TRAINING</h2>
            <div class="featured-cards swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="card shadow">
                            <img src="{{ asset('assets/images/featured-card-1.png') }}" class="card-img-top" alt="...">
                            <div class="card-body text-center">
                                <h5 class="card-title">Recovery-Oriented System of Care</h5>
                                <p class="card-text my-3">A recovery-oriented system of care is a network of clinical and nonclinical services and supports that sustain long-term, community-based substance use recovery. While co…</p>
                                <a class="button-primary mt-3" href="#" class="btn btn-primary">Learn more</a>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="card shadow">
                            <img src="{{ asset('assets/images/featured-card-2.png') }}" class="card-img-top" alt="...">
                            <div class="card-body text-center">
                                <h5 class="card-title">Maintaining Professional Boundaries</h5>
                                <p class="card-text my-3">Maintaining Professional Boundaries is a training for helping professionals focusing on the importance of professional boundary setting, common types of boundary crossing.</p>
                                <a class="button-primary mt-3" href="#" class="btn btn-primary">Learn more</a>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="card shadow">
                            <img src="{{ asset('assets/images/featured-card-3.png') }}" class="card-img-top" alt="...">
                            <div class="card-body text-center">
                                <h5 class="card-title">Question. Persuade. Refer. Refer.</h5>
                                <p class="card-text my-3">QPR or Question, Persuade and Refer is a training focused on suicide prevention techniques and conversation strategies. This training also provides resources to help your.</p>
                                <a class="button-primary mt-3" href="#" class="btn btn-primary">Learn more</a>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="card shadow">
                            <img src="{{ asset('assets/images/featured-card-3.png') }}" class="card-img-top" alt="...">
                            <div class="card-body text-center">
                                <h5 class="card-title">Question. Persuade. Refer. Refer.</h5>
                                <p class="card-text my-3">QPR or Question, Persuade and Refer is a training focused on suicide prevention techniques and conversation strategies. This training also provides resources to help your.</p>
                                <a class="button-primary mt-3" href="#" class="btn btn-primary">Learn more</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </section>
    <section class="Learning-Benefits">
        <div class="container d-flex align-items-center justify-content-center flex-wrap">
            <div class="benifits">
                <h6>Learn how you want, where you want</h6>
                <h2 class="mb-3">The Best selection of online courses</h2>
                <p class="mb-5">Lorem ipsum dolor sit amet consectetur. Sagittis posuere morbi nec pellentesque.
                    Turpis pulvinar non
                    vitae at vestibulum habitasse morbi eu. Nunc nunc dui scelerisque sit condimentum</p>
                <div class="sub-beifits">
                    <div class="row">
                        <div class="col flex-wrap">
                            <div class="d-flex align-items-center gap-2 mb-4">
                                <img src="{{ asset('assets/svgs/benifit-img-1.svg') }}" alt=""><span>Private
                                    Class</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-4">
                                <img src="{{ asset('assets/svgs/benifit-img-2.svg') }}" alt=""><span>Small
                                    Group</span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="d-flex align-items-center gap-2 mb-4">
                                <img src="{{ asset('assets/svgs/benifit-img-3.svg') }}" alt=""><span>Lifetime
                                    Access</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-4">
                                <img src="{{ asset('assets/svgs/benifit-img-4.svg') }}" alt=""><span>Online
                                    Tutoring</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="benifit-img">
                <img src="{{ asset('assets/images/benifit-img.png') }}" alt="">
            </div>
        </div>
    </section>
    <section class="news-letter">
        <div class="container">
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
@push('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const swiper = new Swiper('.swiper', {
            loop: true,
            grabCursor: true,
            spaceBetween: 30,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                0: {
                    slidesPerView: 1
                },
                768: {
                    slidesPerView: 2
                },
                1024: {
                    slidesPerView: 3
                }
            }
        });
    });
</script>
@endpush

