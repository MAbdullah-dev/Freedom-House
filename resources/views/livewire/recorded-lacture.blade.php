<div class="recorded-lacture">
    <div class="container">
        <div class="top-bar d-flex align-items-center justify-content-between">
            <ul class="d-flex align-items-end gap-3">
                <li class="back"><i class="fa-solid fa-arrow-left fa-2xl"></i></li>
                <li>
                    <span class="d-block">{{ $currentLecture->title }}</span>
                    <i class="fa-regular fa-folder-closed fa-sm"></i>
                    {{ $lectures->count() }} Topics
                </li>
                <li><i class="fa-regular fa-circle-play fa-sm video-player"></i> {{ $lectures->count() }}
                    Lectures</li>
                {{-- <li><i class="fa-regular fa-clock fa-sm"></i> {{ $currentLecture->duration }} hours</li> --}}
            </ul>
            <div class="controll-btn">
                @php
                    $prevLecture = $lectures
                        ->where('id', '<', $currentLecture->id)
                        ->sortByDesc('id')
                        ->first();
                    $prevLectureId = $prevLecture ? $prevLecture->id : null;
                    $nextLecture = $lectures
                        ->where('id', '>', $currentLecture->id)
                        ->sortByDesc('id')
                        ->first();
                    $nextLectureId = $nextLecture ? $nextLecture->id : null;
                @endphp


                <a class="button-primary {{ !$prevLectureId ? 'disabled' : '' }}"
                    {{ !$prevLectureId ? '' : 'wire:navigate' }}
                    href="{{ $prevLectureId ? route('recorded.lacture', ['courseId' => $courseId, 'lectureId' => $prevLectureId]) : 'javascript:void(0)' }}">
                    <i class="fa-solid fa-arrow-left fa-xs"></i> Previous
                </a>

                <a class="button-secondary {{ !$nextLectureId ? 'disabled' : '' }}"
                    {{ !$nextLectureId ? '' : 'wire:navigate' }}
                    href="{{ $nextLectureId ? route('recorded.lacture', ['courseId' => $courseId, 'lectureId' => $nextLectureId]) : 'javascript:void(0)' }}">
                    Next <i class="fa-solid fa-arrow-right fa-xs"></i>
                </a>
            </div>

        </div>
        <section class="lacture d-flex mt-5">
            <div class="iframe-container">
                <iframe id="vimeo-player"
                    src="https://player.vimeo.com/video/{{ $videoDetails['uri'] ? explode('/', $videoDetails['uri'])[2] : '' }}"
                    width="640" height="360" frameborder="0" allow="autoplay; fullscreen" allowfullscreen>
                </iframe>

                <h3>{{ $currentLecture->title }}</h3>
                <ul class="d-flex justify-content-between mt-3">
                    <li>Last Updated <span>{{ $currentLecture->updated_at->format('F d, Y') }}</span></li>
                    <li>Comments: {{ $currentLecture->comments_count }}</li>
                </ul>
                <div class="course-details-tabs mt-5">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview"
                                role="tab" aria-controls="overview" aria-selected="true">Overview</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="exercise-files-tab" data-bs-toggle="tab" href="#exercise-files"
                                role="tab" aria-controls="exercise-files" aria-selected="false"
                                tabindex="-1">Exercise Files</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="comments-tab" data-bs-toggle="tab" href="#comments" role="tab"
                                aria-controls="comments" aria-selected="false" tabindex="-1">Comments</a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="myTabContent">
                        <!-- Overview Tab Pane -->
                        <div class="tab-pane fade show active text-start" id="overview" role="tabpanel"
                            aria-labelledby="overview-tab">
                            <div class="course-details__overview my-3">
                                <h4>Course Overview</h4>
                                <p>{{ $currentLecture->overview }}</p>
                            </div>
                        </div>

                        <!-- Exercise Files Tab Pane -->
                        <div class="tab-pane fade" id="exercise-files" role="tabpanel"
                            aria-labelledby="exercise-files-tab">
                            <div class="course-details__widget">
                                <h6>Exercise Files</h6>
                                <p>Here you can access and download all exercise files related to the course.</p>
                            </div>
                        </div>

                        <!-- Comments Tab Pane -->
                        <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                            <div class="course-details__comments">
                                <h4>Comments</h4>
                                <form action="#" method="post" class="w-100">
                                    <div class="d-flex align-items-start gap-3 w-100">
                                        <div class="comment-avatar">
                                            <img src="{{ asset('assets/images/autumn-150x150.webp.png') }}"
                                                alt="User Avatar">
                                        </div>
                                        <div class="comment-textarea">
                                            <label for="commentTextarea" class="visually-hidden">Write your
                                                comment</label>
                                            <textarea id="commentTextarea" name="comment" class="form-control" placeholder="Write your comment here…"
                                                style="height: 100px;"></textarea>
                                            <button class="button-primary mt-3">Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lactures-detail mx-4">
                <div class="lacture-progress">
                    <div class="d-flex justify-content-between">
                        <p>Course Content</p>
                        <p>{{ $progressPercentage }}% completed</p>
                    </div>
                    <progress class="w-100" id="file" value="{{ $progressPercentage }}"
                        max="100">{{ $progressPercentage }}%</progress>
                    <div class="accordion" id="accordionPanelsStayOpenExample">
                        @foreach ($lectures as $lecture)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button {{ $lecture->id === $currentLecture->id ? '' : 'collapsed' }}"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#lecture-{{ $lecture->id }}"
                                        aria-expanded="{{ $lecture->id === $currentLecture->id ? 'true' : 'false' }}"
                                        aria-controls="lecture-{{ $lecture->id }}">
                                        <div>
                                            <span class="d-block">Lecture #{{ $loop->iteration }} <i
                                                    class="fa-solid fa-circle-exclamation ms-4"></i></span>
                                            <ul class="d-flex gap-2 align-items-center my-2">
                                                <li><i class="fa-regular fa-folder-closed fa-sm"></i>
                                                    {{ $lecture->order }} Topics</li>
                                                <li><i class="fa-regular fa-circle-play fa-sm video-player"></i>
                                                    {{ $lecture->order }} Lectures</li>
                                                <li><i class="fa-solid fa-check-double"></i>
                                                    @if ($lecture->watched)
                                                        100% finish (1/1)
                                                    @else
                                                        0% finish (0/1)
                                                    @endif
                                                </li>
                                            </ul>
                                        </div>
                                    </button>
                                </h2>
                                <div id="lecture-{{ $lecture->id }}"
                                    class="accordion-collapse collapse {{ $lecture->id === $currentLecture->id ? 'show' : '' }}">
                                    <div class="accordion-body d-flex align-items-center justify-content-between">
                                        <div>
                                            <input type="radio" {{ $lecture->watched ? 'checked' : 'disabled' }}
                                                name="class" id="class-{{ $lecture->id }}">
                                            <span class="ms-2">{{ $lecture->title }}</span>
                                        </div>
                                        <div>
                                            <span class="class-duration">
                                                Total Duration: {{ gmdate('H:i:s', $lecture->video_duration) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </section>
    </div>
</div>

<script src="https://player.vimeo.com/api/player.js"></script>
<script>
    console.log('ggg');

    var iframe = document.getElementById('vimeo-player');
    var player = new Vimeo.Player(iframe);

    player.on('timeupdate', function(data) {
        // Check if 10 seconds or less remain in the video
        if (data.duration - data.seconds <= 10) {
            // Notify Livewire or backend when 10 seconds remain
            @this.call('markAsWatched', {{ $currentLecture->id }});

            // Remove the event listener to prevent multiple calls
            player.off('timeupdate');
        }
    });
</script>
