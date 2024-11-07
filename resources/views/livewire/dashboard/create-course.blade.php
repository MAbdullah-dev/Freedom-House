<div class="create-course-main" x-data="{ courseTitle: '', description: '', category: '', file: null, instructors: [] }">
    <header class="custom-header">
        <div class="container-fluid px-5">
            <div class="header-wrapper d-flex justify-content-between align-items-center mb-2">
                <img src="{{ asset('assets/svgs/Brand-NameHeader.png.svg') }}" alt="Logo" class="logo">
                <div class="actions">
                    <button type="button" class="btn button-draft custom-draft-button" @click="$dispatch('save-draft', { courseTitle, description, category, file, instructors })">
                        <i class="fa-regular fa-floppy-disk me-1"></i>Save as Draft
                    </button>
                    <button type="button" class="btn button-preview me-2 custom-preview-button">Preview</button>
                    <button type="button" class="btn button-submit custom-submit-button" @click="$dispatch('submit-course', { courseTitle, description, category, file, instructors })">Submit</button>
                    <a href="" class="custom-close-link ms-1">
                        <i class="fa-duotone fa-solid fa-xmark fa-2xl text-secondary"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container my-5">
        <form wire:submit.prevent="submitCourse">
            <div class="row">
                <div class="col-lg-8">
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
                                            <label for="courseTitle" class="form-label">Course Title</label>
                                            <input type="text" class="form-control" id="courseTitle" x-model="courseTitle" placeholder="New Course">
                                        </div>
                                        <!-- Description -->
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" rows="4" x-model="description" placeholder="Enter Description"></textarea>
                                        </div>
                                        <!-- Category Dropdown -->
                                        <label class="mt-3 mb-1" for="course_category">Choose a Category</label>
                                        <div class="dropdown">
                                            <input type="text" id="course_category" class="form-control dropdown-toggle"
                                                x-model="category" placeholder="Search Course Category" data-bs-toggle="dropdown"
                                                aria-expanded="false" autocomplete="off">
                                            <ul class="dropdown-menu w-100" aria-labelledby="course_category">
                                                <li><button class="dropdown-item" type="button" @click="category = 'Ethics'">Ethics</button></li>
                                                <li><button class="dropdown-item" type="button" @click="category = 'Featured'">Featured</button></li>
                                                <li><button class="dropdown-item" type="button" @click="category = 'Mental Health'">Mental Health</button></li>
                                            </ul>
                                        </div>
                                        <!-- Course Thumbnail -->
                                        <div class="row mt-4 align-items-center justify-content-between">
                                            <div class="col-md-4">
                                                <label class="form-label"><strong>Course Thumbnail</strong></label>
                                                <div class="image-placeholder mb-3" id="thumbnailPreview">
                                                    <img :src="file ? URL.createObjectURL(file) : '{{ asset('assets/images/dummy-course-img.png') }}'"
                                                         style="width: 250px; height: 150px" alt="Course Thumbnail" id="previewImage">
                                                </div>
                                            </div>
                                            <div class="col-md-5 d-flex flex-column justify-content-center">
                                                <p class="mb-1 text-secondary">Size: 700x430 pixels</p>
                                                <p class="mb-2 text-secondary">File Support: JPG, PNG</p>
                                                <input type="file" id="imageUpload" accept="image/*" style="display: none;" @change="file = $event.target.files[0]">
                                                <a class="btn btn-success" @click="$refs.imageUpload.click()">
                                                    <i class="bi bi-upload me-1"></i> Upload Image
                                                </a>
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
                                    data-bs-target="#collapseVideo" aria-expanded="false" aria-controls="collapseVideo">
                                    Video
                                </button>
                            </h2>
                            <div id="collapseVideo" class="accordion-collapse collapse" aria-labelledby="headingVideo">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label for="videoUpload" class="form-label">Upload Video URL</label>
                                        <input type="url" class="form-control" id="videoUpload">
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
                            <div id="collapseInstructors" class="accordion-collapse collapse" aria-labelledby="add_instructors">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <div class="instructor-card">
                                            <div class="instructor-card-wrapper my-3 d-flex align-items-center">
                                                <div class="instructor-avatar">
                                                    <img src="{{ asset('assets/images/autumn-150x150.webp.png') }}" alt="Autumn Green" class="avatar-image">
                                                </div>
                                                <div class="instructor-info">
                                                    <div class="instructor-header">
                                                        <span class="instructor-name">Autumn Green</span>
                                                        <img src="https://freedomhousetraining.org/wp-content/plugins/tutor-pro/addons/tutor-multi-instructors/assets/images/crown.svg"
                                                            alt="Instructor Badge" class="instructor-badge">
                                                    </div>
                                                    <div class="instructor-email">Autumn.g@freedomhouserecovery.org
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="add-instructor-btn">
                                                <button type="button" class="button-secondary" @click="instructors.push('Autumn Green')">Add Instructor</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Data -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="add_Additional_Data">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseAdditional_Data" aria-expanded="false"
                                    aria-controls="collapseAdditional_Data">
                                    Additional Data
                                </button>
                            </h2>
                            <div id="collapseAdditional_Data" class="accordion-collapse collapse"
                                aria-labelledby="add_Additional_Data">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label for="">What Will I Learn?</label>
                                        <textarea type="text" placeholder="Write here the course benefits (One per line)" class="form-control"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="">Targeted Audience</label>
                                        <textarea type="text"
                                            placeholder="Specify the target audience that will benefit the most from the course. (One line per target audience)"
                                            class="form-control"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="">Requirements/Instructions</label>
                                        <textarea type="text"
                                            placeholder="Additional requirements or special instructions for the students. (One per line)"
                                            class="form-control"></textarea>
                                    </div>
                                    <div class="dropdown">
                                        <input type="text" id="course_tags" class="form-control dropdown-toggle"
                                            placeholder="Course Tags" data-bs-toggle="dropdown" aria-expanded="false"
                                            autocomplete="off">
                                        <ul class="dropdown-menu w-100" aria-labelledby="course_tags">
                                            <li><button class="dropdown-item" type="button">Training</button></li>
                                            <li><button class="dropdown-item" type="button">Substance Abuse Training</button></li>
                                            <li><button class="dropdown-item" type="button">Mental Health First Aid</button></li>
                                        </ul>
                                    </div>
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

<script>
    // This handles the Alpine.js components
    document.addEventListener('alpine:init', () => {
        Alpine.store('courseStore', {
            courseTitle: '',
            description: '',
            category: '',
            file: null,
            instructors: []
        });
    });
</script>
