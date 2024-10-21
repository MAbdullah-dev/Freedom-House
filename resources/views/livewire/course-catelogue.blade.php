<style>
    .course-catelogue-main{
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
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="most-popular-tab" data-bs-toggle="tab" href="#most-popular" role="tab" aria-controls="most-popular" aria-selected="true">Most Popular</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="new-tab" data-bs-toggle="tab" href="#new" role="tab" aria-controls="new" aria-selected="false">New</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="trending-tab" data-bs-toggle="tab" href="#trending" role="tab" aria-controls="trending" aria-selected="false">Trending</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="onsite-tab" data-bs-toggle="tab" href="#onsite" role="tab" aria-controls="onsite" aria-selected="false">Onsite</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="virtual-tab" data-bs-toggle="tab" href="#virtual" role="tab" aria-controls="virtual" aria-selected="false">Virtual</a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="myTabContent">
                <!-- Most Popular Content -->
                <div class="tab-pane fade show active" id="most-popular" role="tabpanel" aria-labelledby="most-popular-tab">
                    <h3>Most Popular Content</h3>
                    <p>Here is the content for the Most Popular tab.</p>
                </div>

                <!-- New Content -->
                <div class="tab-pane fade" id="new" role="tabpanel" aria-labelledby="new-tab">
                    <h3>New Content</h3>
                    <p>Here is the content for the New tab.</p>
                </div>

                <!-- Trending Content -->
                <div class="tab-pane fade" id="trending" role="tabpanel" aria-labelledby="trending-tab">
                    <h3>Trending Content</h3>
                    <p>Here is the content for the Trending tab.</p>
                </div>

                <!-- Onsite Content -->
                <div class="tab-pane fade" id="onsite" role="tabpanel" aria-labelledby="onsite-tab">
                    <h3>Onsite Content</h3>
                    <p>Here is the content for the Onsite tab.</p>
                </div>

                <!-- Virtual Content -->
                <div class="tab-pane fade" id="virtual" role="tabpanel" aria-labelledby="virtual-tab">
                    <h3>Virtual Content</h3>
                    <p>Here is the content for the Virtual tab.</p>
                </div>
            </div>
        </div>
    </section>

</div>
