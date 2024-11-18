<div class="swiper">
    <div class="arrows d-flex align-items-center justify-content-end gap-2">
        <div class="swiper-button-prev position-relative"></div>
        <div class="swiper-button-next position-relative"></div>
    </div>
    <div class="swiper-wrapper">
        @foreach ($mostPopularCourses as $mostPopularCourse)
            <div class="swiper-slide">
                <div class="card shadow">
                    <div class="card-img-top">
                        <img src="{{ asset('assets/images/featured-card-1.png') }}" class="card-img-top" alt="...">

                        <!-- Wishlist Icon with Dynamic Class Toggle -->
                        <i class="fa-regular fa-bookmark wishlist-icon"
                            wire:click="toggleWishlist({{ $mostPopularCourse->id }})"
                            class="{{ $mostPopularCourse->wishlist ? 'fa-solid' : 'fa-regular' }} wishlist-icon"></i>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{ $mostPopularCourse->title }}</h5>
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
                                <small>by</small><span>{{ $mostPopularCourse->user->first_name }}
                                    {{ $mostPopularCourse->user->last_name }}</span>
                            </div>
                        </div>
                        <p class="card-text ">{{ $mostPopularCourse->description }}</p>
                        <div class="post-details d-flex align-items-center">
                            <span class="date">Publish: {{ $mostPopularCourse->created_at->diffForHumans() }}</span>
                        </div>
                        <a class="button-primary mt-3 d-block w-100 text-center"
                            href="{{ route('course.details', $mostPopularCourse->id) }}">More Details...</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
