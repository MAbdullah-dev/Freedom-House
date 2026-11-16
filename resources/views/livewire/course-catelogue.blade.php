@php
    $user = Auth::user();
@endphp
<div class="course-catalogue">
    @push('title')
        Course Catalog
    @endpush
    <section class="public-hero">
        <div class="container">
            <p class="public-hero__kicker">Stellix Soft LMS</p>
            <h1>Course catalog</h1>
            <p class="public-hero__lead">Search published courses and filter them by the categories stored in the catalog.</p>
        </div>
    </section>
    <section class="course-catelogue-list">
        <div class="container mt-5">
            <div x-data="{ tab: 'mostPopular' }">
                <ul class="nav nav-tabs public-tabs">
                    <li class="nav-item">
                        <button class="nav-link" type="button" :class="{ 'active': tab === 'mostPopular' }" @click="tab = 'mostPopular'">Most popular</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" type="button" :class="{ 'active': tab === 'new' }" @click="tab = 'new'">New</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" type="button" :class="{ 'active': tab === 'trending' }" @click="tab = 'trending'">Trending</button>
                    </li>
                </ul>
                <div class="tab-content mt-4">
                    <div x-show="tab === 'mostPopular'" x-cloak>
                        <livewire:courses-cards.most-popular-cards />
                    </div>
                    <div x-show="tab === 'new'" x-cloak>
                        <livewire:courses-cards.new-cards />
                    </div>
                    <div x-show="tab === 'trending'" x-cloak>
                        <livewire:courses-cards.trending-cards />
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="course-catelogue pt-3">
        <div class="container">
            <div class="filters d-flex justify-content-between align-items-center gap-3 mb-4">
                <div class="input-group filter-search">
                    <label class="visually-hidden" for="catalog-search">Search courses by title</label>
                    <input id="catalog-search" type="search" placeholder="Search courses by title" wire:model.live="search" class="form-control" />
                </div>
                <button class="button-secondary d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#catalogFilters" aria-controls="catalogFilters">
                    Filters
                </button>
            </div>

            <div class="offcanvas offcanvas-end" tabindex="-1" id="catalogFilters" aria-labelledby="catalogFiltersLabel">
                <div class="offcanvas-header">
                    <h2 class="offcanvas-title h5" id="catalogFiltersLabel">Filters</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <label class="visually-hidden" for="catalog-search-mobile">Search courses by title</label>
                    <input id="catalog-search-mobile" type="search" class="form-control mb-3" placeholder="Search courses by title" wire:model.live="search">
                    <h3 class="h6">Category</h3>
                    <ul class="public-filter-list">
                        @forelse ($categories as $category)
                            <li>
                                <input type="checkbox" id="mobile-category-{{ $category->id }}" value="{{ $category->id }}" wire:change="selectedCategorieschanged({{ $category->id }})">
                                <label for="mobile-category-{{ $category->id }}">{{ $category->category_name }}</label>
                            </li>
                        @empty
                            <li>No categories are available.</li>
                        @endforelse
                    </ul>
                    <button class="button-secondary" type="button" wire:click="clearFilters">Clear filters</button>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-3 d-none d-xl-block">
                    <div class="filter public-filter">
                        <h2 class="h5">Category</h2>
                        <ul class="public-filter-list">
                            @forelse ($categories as $category)
                                <li>
                                    <input type="checkbox" id="category-{{ $category->id }}" value="{{ $category->id }}" wire:change="selectedCategorieschanged({{ $category->id }})">
                                    <label for="category-{{ $category->id }}">{{ $category->category_name }}</label>
                                </li>
                            @empty
                                <li>No categories are available.</li>
                            @endforelse
                        </ul>
                        <button class="button-secondary" type="button" wire:click="clearFilters">Clear filters</button>
                    </div>
                </div>
                <div class="col-xl-9">
                    <div class="row gy-4">
                        @forelse ($courses as $item)
                            <div class="col-sm-6 col-lg-4">
                                <x-public-course-card :course="$item">
                                    <x-slot:bookmark>
                                        @if ($user)
                                            <button class="wishlist-toggle" type="button" wire:click="toggleWishlist({{ $item->id }})" aria-label="Save {{ $item->title }}">
                                                <i class="{{ $user->wishlist->contains($item) ? 'fa-solid' : 'fa-regular' }} fa-bookmark" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            <button class="wishlist-toggle" type="button" data-bs-toggle="modal" data-bs-target="#AuthCheckModal" aria-label="Sign in to save this course">
                                                <i class="fa-regular fa-bookmark" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </x-slot:bookmark>
                                </x-public-course-card>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="public-empty">No published courses match this search.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="instructor-slider">
        <div class="container">
            <h2>Tutors</h2>
            <livewire:courses-cards.instructor-cards />
        </div>
    </div>
</div>
@script
    <script type="text/javascript">
        document.querySelectorAll('.course-catalogue .swiper').forEach(function(element) {
            if (!element.querySelector('.swiper-slide')) {
                return;
            }
            new Swiper(element, {
                loop: false,
                grabCursor: true,
                navigation: {
                    nextEl: element.querySelector('.swiper-button-next'),
                    prevEl: element.querySelector('.swiper-button-prev'),
                },
                breakpoints: {
                    320: { slidesPerView: 1, spaceBetween: 16 },
                    768: { slidesPerView: 2, spaceBetween: 16 },
                    1024: { slidesPerView: 3, spaceBetween: 16 }
                }
            });
        });

        document.addEventListener('clearCategoryFilters', () => {
            document.querySelectorAll('.course-catalogue .filter input[type="checkbox"], #catalogFilters input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    </script>
@endscript
