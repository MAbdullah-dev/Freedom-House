<div>
    @if ($trendingCourses->isEmpty())
        <p class="public-empty">No courses in this list yet.</p>
    @else
        <div class="swiper trending-cards" wire:ignore>
            <div class="arrows d-flex align-items-center justify-content-end gap-2">
                <div class="swiper-button-prev position-relative"></div>
                <div class="swiper-button-next position-relative"></div>
            </div>
            <div class="swiper-wrapper">
                @foreach ($trendingCourses as $trendingCourse)
                    <div class="swiper-slide">
                        <x-public-course-card :course="$trendingCourse">
                            <x-slot:bookmark>
                                @if (Auth::check())
                                    <i class="{{ Auth::user()->wishlist->contains($trendingCourse) ? 'fa-solid' : 'fa-regular' }} fa-bookmark wishlist-icon"
                                        data-course-id="{{ $trendingCourse->id }}" role="button" tabindex="0"
                                        aria-label="Save {{ $trendingCourse->title }}"></i>
                                @else
                                    <i class="fa-regular fa-bookmark" role="button" tabindex="0" aria-label="Sign in to save this course"
                                        data-bs-toggle="modal" data-bs-target="#AuthCheckModal"></i>
                                @endif
                            </x-slot:bookmark>
                        </x-public-course-card>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@push('js')
    <script>
        $(document).ready(function() {
            $('.trending-cards .wishlist-icon').off('click.stellixTrendingWishlist');
            $('.trending-cards .wishlist-icon').on('click.stellixTrendingWishlist', function() {
                var icon = $(this);
                @this.call('toggleWishlist', icon.data('course-id'));
                icon.toggleClass('fa-regular fa-solid');
            });
        });
    </script>
@endpush
