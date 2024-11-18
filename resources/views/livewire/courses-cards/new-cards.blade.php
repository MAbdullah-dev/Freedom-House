<div class="swiper">
    <div class="arrows d-flex align-items-center justify-content-end gap-2">
        <div class="swiper-button-prev position-relative"></div>
        <div class="swiper-button-next position-relative"></div>
    </div>
    <div class="swiper-wrapper">
        @foreach ($newCourses as $newCourse)
            <div class="swiper-slide">
                <div class="card shadow">
                    <div class="card-img-top">
                        <img src="{{ asset('assets/images/featured-card-1.png') }}" class="card-img-top" alt="...">
                        <i class="fa-regular fa-bookmark"></i>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{ $newCourse->title }}</h5>
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
                                <small>by</small><span>{{ $newCourse->user->first_name }}
                                    {{ $newCourse->user->last_name }}</span>
                            </div>
                            {{-- <div class="tutor-meta-icon d-flex align-items-center gap-2">
                                <div class="meta-icon d-flex align-items-center">
                                    <i class="fa-regular fa-user" style="color: #757c8e;"></i>
                                    <span>11</span>
                                </div>
                                <div class="meta-icon d-flex align-items-center">
                                    <i class="fa-regular fa-clock" style="color: #757c8e;"></i>
                                    <span>2h</span>
                                </div>
                            </div> --}}
                        </div>
                        <p class="card-text ">{{ $newCourse->description }}</p>
                        <div class="post-details d-flex align-items-center">
                            <span class="date">Publish
                                :{{ $newCourse->created_at->diffForHumans() }}</span>
                        </div>
                        <a class="button-primary mt-3 d-block w-100 text-center"
                            href="{{ route('course.details', $newCourse->id) }}">More
                            Details...</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
