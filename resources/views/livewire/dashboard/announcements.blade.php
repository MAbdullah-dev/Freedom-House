<div class="announcements px-4">
    <section class="announcement-card py-3 my-3">
        <div class="row align-content-center justify-content-center">
            <div class="col-7">
                <ul class="d-flex align-items-center gap-3">
                    <li>
                        <div class="announcement-icon d-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                    </li>
                    <li>
                        <div class="announcement-text">
                            <span>Create Announcement</span>
                            <p>Notify all students of your course</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-5 d-flex justify-content-center align-items-center">
                <button class="button-primary">Add new Announcement</button>
            </div>
        </div>
    </section>
    <section class="filter-courses">
        <div class="row">
            <div class="col-6">
                <label for="courses" class="form-label">Courses</label>
                <div class="dropdown">
                    <button class="dropdown-toggle w-100 d-flex justify-content-between align-items-center"
                        type="button" id="courseDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        All
                    </button>
                    <ul class="dropdown-menu p-3 w-100" id="courseList" aria-labelledby="courseDropdown">
                        <div class="input-group mb-2 position-relative">
                            <i class="fas fa-search position-absolute" aria-hidden="true"></i>
                            <input type="text" class="form-control" id="courseSearchInput"
                                placeholder="Search courses..." />
                        </div>
                        <li><a class="dropdown-item" href="#">Understanding Personality Disorders</a></li>
                        <li><a class="dropdown-item" href="#">Maintain Professional Boundaries</a></li>
                        <li><a class="dropdown-item" href="#">Clinical Documentation</a></li>
                        <li><a class="dropdown-item" href="#">Mental Health Basics</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-3">
                <div class="dropdown flex-grow-1">
                    <label for="sortOrder" class="form-label">Sort Order</label>
                    <button class="dropdown-toggle w-100 d-flex justify-content-between align-items-center"
                        type="button" id="sortOrderDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        DESC
                    </button>
                    <ul class="dropdown-menu p-3 w-100" aria-labelledby="sortOrderDropdown">
                        <input type="text" class="form-control mb-2" id="sortOrderSearchInput"
                            placeholder="Search sort order..." />
                        <li><a class="dropdown-item" href="#">ASC</a></li>
                        <li><a class="dropdown-item" href="#">DESC</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-3">
                <div class="date-input flex-grow-1">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="calendar-input-students-tab" placeholder="Y-M-d" />
                </div>
            </div>
        </div>
    </section>
    @php
        $announcements = collect([
            // ['date' => '2023-05-01', 'message' => 'Announcement 1'],
            // ['date' => '2023-05-02', 'message' => 'Announcement 2'],
            // ['date' => '2023-05-03', 'message' => 'Announcement 3'],
        ]);
    @endphp
    <div class="announcment-table mt-5">
        <div class="card-table">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Announcment</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($announcements->isEmpty())
                        <!-- Check if the announcements collection is empty -->
                        <tr>
                            <td colspan="2" class="text-center">
                                <img src="{{ asset('assets/images/announcment.png') }}" alt="No Announcements" />
                            </td>
                        </tr>
                    @else
                        @foreach ($announcements as $announcement)
                            <!-- Assuming you have an announcements collection -->
                            <tr>
                                <td>{{ $announcement['date'] }}</td>
                                <td>{{ $announcement['message'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

    </div>
</div>
@script
    <script>
        $(document).ready(function() {
            // Search functionality for courses dropdown
            $('#courseSearchInput').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('#courseList li').each(function() {
                    var courseName = $(this).text().toLowerCase();
                    if (courseName.includes(searchTerm)) {
                        $(this).show(); // Show the course if it matches
                    } else {
                        $(this).hide(); // Hide the course if it doesn't match
                    }
                });
            });

            // Search functionality for sort order dropdown
            $('#sortOrderSearchInput').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('#sortOrderDropdown .dropdown-menu li').each(function() {
                    var sortOrderName = $(this).text().toLowerCase();
                    if (sortOrderName.includes(searchTerm)) {
                        $(this).show(); // Show the sort option if it matches
                    } else {
                        $(this).hide(); // Hide the sort option if it doesn't match
                    }
                });
            });
        });

        // Initialize Flatpickr
        flatpickr("#calendar-input-students-tab", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
        });
    </script>
@endscript
