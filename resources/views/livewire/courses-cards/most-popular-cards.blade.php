<div>
    @if ($mostPopularCourses->isEmpty())
        <p class="public-empty">No courses in this list yet.</p>
    @else
<div class="swiper popular-cards" wire:ignore>
    <div class="arrows d-flex align-items-center justify-content-end gap-2">
        <div class="swiper-button-prev position-relative"></div>
        <div class="swiper-button-next position-relative"></div>
    </div>
    <div class="swiper-wrapper">
        @foreach ($mostPopularCourses as $mostPopularCourse)
            <div class="swiper-slide">
                <x-public-course-card :course="$mostPopularCourse">
                    <x-slot:bookmark>
                        @if (Auth::check())
                            <i class="{{ Auth::user()->wishlist->contains($mostPopularCourse) ? 'fa-solid' : 'fa-regular' }} fa-bookmark wishlist-icon"
                                data-course-id="{{ $mostPopularCourse->id }}" role="button" tabindex="0"
                                aria-label="Save {{ $mostPopularCourse->title }}"></i>
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
            // Handle wishlist icon click for all icons dynamically
            $('.popular-cards .wishlist-icon').off('click.stellixWishlist');
            $('.popular-cards .wishlist-icon').on('click.stellixWishlist', function() {
                var courseId = $(this).data('course-id');
                var icon = $(this);

                // Call Livewire method without re-rendering the entire component
                @this.call('toggleWishlist', courseId);

                // Toggle the icon class after Livewire call
                if (icon.hasClass('fa-regular')) {
                    icon.removeClass('fa-regular').addClass('fa-solid');
                } else {
                    icon.removeClass('fa-solid').addClass('fa-regular');
                }
            });
        });
    </script>
@endpush
