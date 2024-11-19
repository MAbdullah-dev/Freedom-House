<div class="create-course-main">
    <header class="custom-header">
        <div class="container-fluid px-5">
            <div class="header-wrapper d-flex justify-content-between align-items-center mb-2">
                <img src="{{ asset('assets/svgs/Brand-NameHeader.png.svg') }}" alt="Logo" class="logo">
                <div class="actions">
                    <button type="button" class="btn button-draft custom-draft-button" wire:click="saveAsDraft">
                        <i class="fa-regular fa-floppy-disk me-1"></i>Save as Draft
                    </button>
                    <button type="button" class="btn button-preview me-2 custom-preview-button">Preview</button>
                    <button type="submit" class="btn button-submit custom-submit-button" wire:click="submitCourse"
                        wire:loading.attr="disabled">
                        <span wire:loading wire:target="submitCourse" class="spinner-border spinner-border-sm"
                            aria-hidden="true"></span>
                        <span wire:loading.remove
                            wire:target="submitCourse">{{ $courseId ? 'Update Course' : 'Create Course' }}</span>
                        <span wire:loading wire:target="submitCourse">Loading...</span>
                    </button>

                    <a href="" class="custom-close-link ms-1">
                        <i class="fa-duotone fa-solid fa-xmark fa-2xl text-secondary"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>
    <!-- Loader Overlay -->
    <div id="loaderOverlay" wire:loading.class="d-flex" wire:loading wire:target="submitCourse"
        class="d-none align-items-center justify-content-center">
        <div class="overlay-bg"></div>
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <div class="container my-5">
        <form>
            <div class="row">
                <div class="col-lg-8" style="position: relative" class="form w-75 w-lg-50 w-md-75 w-sm-100 mx-auto">
                    <div wire:loading wire:target="submitCourse" class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                    <div class="accordion" id="courseAccordion">
                        <!-- Course Info Accordion Item -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingCourseInfo">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseCourseInfo" aria-expanded="true"
                                    aria-controls="collapseCourseInfo">
                                    Course Info
                                </button>
                            </h2>
                            <div id="collapseCourseInfo" class="accordion-collapse collapse show"
                                aria-labelledby="headingCourseInfo">
                                <div class="accordion-body">
                                    <div class="course-main-content mb-4">
                                        <!-- Course Title -->
                                        <div class="mb-3">
                                            <label for="courseTitle">Course Title</label>
                                            <input type="text" wire:model="courseTitle" class="form-control"
                                                id="courseTitle" required>
                                            @error('courseTitle')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <!-- Description -->
                                        <div class="mb-3">
                                            <label for="description">Description</label>
                                            <textarea wire:model="description" class="form-control" id="description" required></textarea>
                                            @error('description')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <label for="category">Categories</label>
                                        <div wire:ignore class="d-flex align-items-center select-wrapper">
                                            <!-- Category Dropdown -->
                                            <select class="form-control" id="category" multiple required>
                                                @foreach ($categorylist as $category)
                                                    <option value='{{ $category->id }}'>{{ $category->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('category')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                        <!-- Course Thumbnail -->
                                        <div class="row mt-4 align-items-center justify-content-between">
                                            <div class="col-md-4">
                                                <label class="form-label"><strong>Course Thumbnail</strong></label>
                                                <div class="image-placeholder mb-3">
                                                    <!-- Display the uploaded image or fallback to a placeholder image -->
                                                    <img src="{{ $file ? $file->temporaryUrl() : asset('assets/images/dummy-course-img.png') }}"
                                                        style="width: 250px; height: 150px" alt="Course Thumbnail"
                                                        id="previewImage">
                                                </div>
                                            </div>
                                            <div class="col-md-5 d-flex flex-column justify-content-center">
                                                <p class="mb-1 text-secondary">Size: 700x430 pixels</p>
                                                <p class="mb-2 text-secondary">File Support: JPG, PNG</p>

                                                <!-- Loader spinner that shows when the file is being uploaded -->
                                                <div wire:loading wire:target="file" class="spinner-border text-success"
                                                    role="status">
                                                    <span class="visually-hidden">Uploading...</span>
                                                </div>

                                                <!-- Input for file upload -->
                                                <label for="file-upload" class="custom-file-upload button-primary mt-3">
                                                    <i class="bi bi-upload me-1"></i> Upload Image
                                                </label>
                                                <input wire:model="file" id="file-upload" type="file" />
                                                @error('file')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Video Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingVideo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseVideo" aria-expanded="false"
                                    aria-controls="collapseVideo">
                                    Video
                                </button>
                            </h2>
                            <div id="collapseVideo" class="accordion-collapse collapse"
                                aria-labelledby="headingVideo">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label for="videoUpload" class="form-label">Upload Video URL</label>
                                        <input type="url" class="form-control" id="videoUpload"
                                            wire:model="videoUrl" placeholder="https://example.com/video.mp4">
                                        @error('videoUrl')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Instructors -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="add_instructors">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseInstructors" aria-expanded="false"
                                    aria-controls="collapseInstructors">
                                    Instructors
                                </button>
                            </h2>
                            <div id="collapseInstructors" class="accordion-collapse collapse show"
                                aria-labelledby="add_instructors">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <div class="instructor-card">

                                            <div class="" id="instructorDropdown">
                                                <div wire:ignore class="d-flex align-items-center select-wrapper my-2">
                                                    <select class="form-control" id="tutorSelect" multiple
                                                        wire:model="tutors" required>
                                                        @foreach ($tutorList as $tutor)
                                                            <option value="{{ $tutor->user_id }}">
                                                                {{ $tutor->user->email }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error('tutors')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            {{-- <div class="add-instructor-btn">
                                                <button type="button" class="button-secondary"
                                                    id="addInstructorBtn">Add Instructor</button>
                                            </div> --}}
                                            @foreach ($selectedTutors as $tutor)
                                                <div class="instructor-card-wrapper my-3 d-flex align-items-center">
                                                    <div class="instructor-avatar">
                                                        <img src="{{ asset('assets/images/autumn-150x150.webp.png') }}"
                                                            alt="Autumn Green" class="avatar-image">
                                                    </div>
                                                    <div class="instructor-info">
                                                        <div class="instructor-header">
                                                            <span
                                                                class="instructor-name">{{ $tutor->user->first_name }}
                                                                {{ $tutor->user->last_name }}</span>
                                                            <img src="https://freedomhousetraining.org/wp-content/plugins/tutor-pro/addons/tutor-multi-instructors/assets/images/crown.svg"
                                                                alt="Instructor Badge" class="instructor-badge">
                                                        </div>
                                                        <div class="instructor-email">{{ $tutor->user->email }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Data -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="add_Additional_Data">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseAdditional_Data" aria-expanded="true"
                                    aria-controls="collapseAdditional_Data">
                                    Additional Data
                                </button>
                            </h2>
                            <div id="collapseAdditional_Data" class="accordion-collapse collapse show"
                                aria-labelledby="add_Additional_Data">
                                <div class="accordion-body">
                                    <!-- What Will I Learn? Textarea -->
                                    <div class="mb-3">
                                        <label for="learnDetails">What Will I Learn?</label>
                                        <textarea wire:model="learnDetails" class="form-control" id="learnDetails"
                                            placeholder="Write here the course benefits (One per line)"></textarea>
                                        @error('learnDetails')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Targeted Audience Textarea -->
                                    <div class="mb-3">
                                        <label for="audienceDetails">Targeted Audience</label>
                                        <textarea wire:model="audienceDetails" class="form-control" id="audienceDetails"
                                            placeholder="Specify the target audience that will benefit the most from the course. (One line per target audience)"></textarea>
                                        @error('audienceDetails')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Requirements/Instructions Textarea -->
                                    <div class="mb-3">
                                        <label for="requirements">Requirements/Instructions</label>
                                        <textarea wire:model="requirements" class="form-control" id="requirements"
                                            placeholder="Additional requirements or special instructions for the students. (One per line)"></textarea>
                                        @error('requirements')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Tags Select2 Dropdown -->
                                    <label for="tags" class="me-2">Tags:</label>
                                    <div wire:ignore class="d-flex align-items-center select-wrapper">
                                        <select class="form-control select2 w-100" id="tags" multiple required>
                                            @foreach ($tagslist as $tag)
                                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('tags')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Course Upload Tips -->
                <div class="col-lg-4">
                    <div class="card p-3 mb-3 custom-upload-tips-card">
                        <h5 class="card-title">Course Upload Tips</h5>
                        <ul class="custom-upload-tips-list">
                            <li>Set the Course Price option or make it free.</li>
                            <li>Standard size for the course thumbnail is 700x430.</li>
                            <li>Video section controls the course overview video.</li>
                            <li>Course Builder is where you create & organize a course.</li>
                            <li>Add Topics in the Course Builder to create lessons, quizzes, and assignments.</li>
                            <li>Prerequisites refer to required courses before this one.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@script
    <script>
        $(document).ready(function() {
            // Initialize Select2 on the category, tags, and tutorSelect dropdowns
            $('#category, #tags, #tutorSelect').select2({
                // placeholder: 'Select options',
                allowClear: true,
                multiple: true,
                closeOnSelect: false
            });

            // Handle change events for category and tags
            $('#category').on('change', function() {
                let data = $(this).val(); // Get the selected values
                @this.set('category', data); // Set Livewire category model
            });

            $('#tags').on('change', function() {
                let data = $(this).val(); // Get the selected values
                @this.set('tags', data); // Set Livewire tags model
            });

            $('#tutorSelect').on('change', function() {
                let data = $(this).val(); // Get the selected values
                @this.set('tutors', data); // Set Livewire tutors model
            });

            // Listen for Livewire dispatch event 'clearSelect2' to clear Select2 fields
            Livewire.on('clearSelect2', () => {
                $('#category, #tags, #tutorSelect').val(null).trigger('change');
            });

            // Toggle the visibility of the instructor dropdown when the button is clicked
            // $('#addInstructorBtn').on('click', function() {
            //     $('#instructorDropdown').toggleClass('d-none');
            // });
        });
    </script>
@endscript
