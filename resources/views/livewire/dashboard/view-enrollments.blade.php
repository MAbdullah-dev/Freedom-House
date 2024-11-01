<div class="view-enrollments px-4">
    <section class="enrollments my-5 mx-4">
        <div class="tutor-avatar d-flex align-items-center gap-2 mb-5">
            <img src="{{ asset('assets/images/enrollment-profile.png') }}" alt="tutor" />
            <h6>M Abdullah</h6>
        </div>
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="mb-3" id="calendar-input" placeholder="Select date" />
            </div>
            <div class="col-md-3">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button"
                        id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-globe icon"></i>
                        <span id="selected-timezone">-04:00</span> <!-- This span will show the selected timezone -->
                    </button>
                    <ul class="dropdown-menu custom-dropdown-menu w-100" id="timezoneList"
                        aria-labelledby="dropdownMenuButton">
                        <input type="text" id="timezoneSearchInput" class="form-control mb-2"
                            placeholder="Search timezone">
                        <li><a class="dropdown-item" href="#">Africa/Abidjan</a></li>
                        <li><a class="dropdown-item" href="#">Africa/Accra</a></li>
                        <li><a class="dropdown-item" href="#">Africa/Addis_Ababa</a></li>
                        <li><a class="dropdown-item" href="#">Africa/Algiers</a></li>
                        <li><a class="dropdown-item" href="#">Africa/Asmara</a></li>
                        <li><a class="dropdown-item" href="#">Africa/Bamako</a></li>
                        <li><a class="dropdown-item" href="#">Africa/Banjul</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-3">
                <button type="button" class="button-primary" data-bs-toggle="modal" data-bs-target="#create-class"><i
                        class="fa-solid fa-plus"></i> New Event</button>
            </div>
        </div>
        <div class="no-enrollment-img d-flex justify-content-center align-content-center mt-5">
            <img src="{{ asset('assets/images/no-enrollment.png') }}" alt="no-enrollment" />
        </div>
    </section>
    <!-- Bootstrap Modal for Multistep Form -->
    <div class="modal fade" id="create-class" tabindex="-1" role="dialog" aria-labelledby="multiStepModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Create Class</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Step Navigation -->
                    <div class="steps d-flex justify-content-between mb-4">
                        <span class="step-indicator" data-step="1">Step 1</span>
                        <span class="step-indicator" data-step="2">Step 2</span>
                    </div>

                    <!-- Multistep Form -->
                    <form id="multiStepForm">
                        <!-- Step 1 -->
                        <div class="step" id="step-1">
                            <div class="form-group">
                                <label for="courses" class="form-label">Courses</label>
                                <div class="dropdown">
                                    <button
                                        class="dropdown-toggle w-100 d-flex justify-content-between align-items-center"
                                        type="button" id="coursesDropdown" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        All
                                    </button>
                                    <ul class="dropdown-menu p-3 w-100" aria-labelledby="coursesDropdown">
                                        <input type="text" class="form-control mb-2"
                                            placeholder="Search courses..." />
                                        <li><a class="dropdown-item" href="#">Understanding Personality
                                                Disorders</a></li>
                                        <li><a class="dropdown-item" href="#">Maintain Professional Boundaries</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#">Clinical Documentation</a></li>
                                        <li><a class="dropdown-item" href="#">Mental Health Basics</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                <label for="classTitle">Class Title</label>
                                <input type="text" class="form-control" id="classTitle" name="class_title"
                                    required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="classDescription">Class Description</label>
                                <textarea class="form-control" id="classDescription" name="class_description" required></textarea>
                            </div>
                            <div class="form-group mt-3">
                                <label for="classType">Class Type</label>
                                <select class="form-select" id="classType" aria-label="Default select example">
                                    <option selected>select Class Type</option>
                                    <option value="Virtual">Virtual</option>
                                    <option value="Recorded">Recorded</option>
                                    <option value="Onsite">Onsite</option>
                                  </select>
                            </div>
                            <div class="form-group mt-3">
                                <label for="isPaid">Free or Paid</label>
                                <select class="form-control" id="isPaid" name="is_paid">
                                    <option value="free">Free</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
                            <div class="form-group d-none mt-3" id="priceField">
                                <label for="price">Price</label>
                                <input type="number" class="form-control" id="price" name="price"
                                    placeholder="Enter price if paid">
                            </div>
                            <button type="button" class="button-primary mt-3" id="nextStep">Next</button>
                        </div>

                        <!-- Step 2 -->
                        <div class="step d-none mt-3" id="step-2">
                            <div class="form-group">
                                <label for="startDateTime">Start Date & Time</label>
                                <input type="datetime-local" class="form-control" id="startDateTime"
                                    name="start_date_time" required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="endDateTime">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="endDateTime"
                                    name="end_date_time" required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="maxStudents">Maximum Students</label>
                                <input type="number" class="form-control" id="maxStudents" name="max_students"
                                    placeholder="Enter maximum students or leave blank for no limit">
                            </div>
                            <div class="conditional-fields">
                                <!-- Fields for "Recorded" -->
                                <div class="form-group d-none mt-3" id="recordedFields">
                                    <label for="recordedVideo">Video of Recorded Class</label>
                                    <input type="file" class="form-control" id="recordedVideo"
                                        name="recorded_video">
                                    <label for="quiz">Quiz (optional)</label>
                                    <input type="text" class="form-control" id="quiz" name="quiz">
                                    <label for="assignment">Assignment (optional)</label>
                                    <input type="text" class="form-control" id="assignment" name="assignment">
                                </div>
                                <!-- Fields for "Virtual" -->
                                <div class="form-group d-none mt-3" id="virtualFields">
                                    <label for="teamsLink">Generate Teams Link for Class</label>
                                    <input type="url" class="form-control" id="teamsLink" name="teams_link">
                                </div>
                                <!-- Fields for "Onsite" -->
                                <div class="form-group d-none mt-3" id="onsiteFields">
                                    <label for="address">Class Address</label>
                                    <input type="text" class="form-control" id="address" name="address">
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary" id="prevStep">Back</button>
                            <button type="submit" class="btn btn-success">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        document.querySelectorAll('.dropdown-item').forEach(function(item) {
            item.addEventListener('click', function(event) {
                // Get the selected timezone text
                const selectedTimezone = event.target.textContent;

                // Update the button text with the selected timezone
                document.getElementById('selected-timezone').textContent = selectedTimezone;
            });
        });
        document.getElementById('timezoneSearchInput').addEventListener('keyup', function() {
            var filter = this.value.toLowerCase();
            var items = document.querySelectorAll('#timezoneList .dropdown-item');

            items.forEach(function(item) {
                if (item.textContent.toLowerCase().indexOf(filter) > -1) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        flatpickr("#calendar-input", {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            minDate: "2024-01-01",
            maxDate: "2030-12-31",
        });
        $(document).ready(function() {
            // Show/hide price field based on free/paid selection
            $('#isPaid').on('change', function() {
                if ($(this).val() === 'paid') {
                    $('#priceField').removeClass('d-none').hide().fadeIn();
                } else {
                    $('#priceField').fadeOut(function() {
                        $(this).addClass('d-none');
                    });
                }
            });

            // Step navigation
            $('#nextStep').on('click', function() {
                $('#step-1').fadeOut(function() {
                    $(this).addClass('d-none');
                    $('#step-2').hide().removeClass('d-none').fadeIn();
                });
            });

            $('#prevStep').on('click', function() {
                $('#step-2').fadeOut(function() {
                    $(this).addClass('d-none');
                    $('#step-1').hide().removeClass('d-none').fadeIn();
                });
            });

            // Show fields based on class type selection
            $('#classType').on('change', function() {
                const selectedType = $(this).val();

                // Hide all conditional fields first
                $('#recordedFields, #virtualFields, #onsiteFields').addClass('d-none').hide();

                if (selectedType === 'recorded') {
                    $('#recordedFields').removeClass('d-none').hide().fadeIn();
                } else if (selectedType === 'virtual') {
                    $('#virtualFields').removeClass('d-none').hide().fadeIn();
                } else if (selectedType === 'onsite') {
                    $('#onsiteFields').removeClass('d-none').hide().fadeIn();
                }
            });
        });
    </script>
@endscript
