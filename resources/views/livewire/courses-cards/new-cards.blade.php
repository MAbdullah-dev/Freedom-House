<div>
    @if ($newCourses->isEmpty())
        <p class="public-empty">No courses in this list yet.</p>
    @else
        <div class="swiper new-cards" wire:ignore>
            <div class="arrows d-flex align-items-center justify-content-end gap-2">
                <div class="swiper-button-prev position-relative"></div>
                <div class="swiper-button-next position-relative"></div>
            </div>
            <div class="swiper-wrapper">
                @foreach ($newCourses as $newCourse)
                    <div class="swiper-slide">
                        <x-public-course-card :course="$newCourse">
                            <x-slot:bookmark>
                                @if (Auth::check())
                                    <i class="{{ Auth::user()->wishlist->contains($newCourse) ? 'fa-solid' : 'fa-regular' }} fa-bookmark wishlist-icon"
                                        data-course-id="{{ $newCourse->id }}" role="button" tabindex="0"
                                        aria-label="Save {{ $newCourse->title }}"></i>
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
            $('.new-cards .wishlist-icon').off('click.stellixNewWishlist');
            $('.new-cards .wishlist-icon').on('click.stellixNewWishlist', function() {
                var icon = $(this);
                @this.call('toggleWishlist', icon.data('course-id'));
                icon.toggleClass('fa-regular fa-solid');
            });
        });
    </script>
@endpush
