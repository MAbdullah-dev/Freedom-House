<div class="students-tab px-5">
    <div class="filters d-flex flex-row flex-md-row gap-3 align-items-start mb-4">
        <div class="flex-grow-1">
            <label for="">Search for Students</label>
            <input type="text" class="form-control search-input" placeholder="Search..." />
        </div>
        <div class="dropdown flex-grow-1">
            <label for="courses" class="form-label">Courses</label>
            <button class="dropdown-toggle w-100 d-flex justify-content-between align-items-center" type="button"
                id="coursesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                All
            </button>
            <ul class="dropdown-menu p-3 w-100" aria-labelledby="coursesDropdown">
                <input type="text" class="form-control mb-2" placeholder="Search courses..." />
                <li><a class="dropdown-item" href="#">Understanding Personality Disorders</a></li>
                <li><a class="dropdown-item" href="#">Maintain Professional Boundaries</a></li>
                <li><a class="dropdown-item" href="#">Clinical Documentation</a></li>
                <li><a class="dropdown-item" href="#">Mental Health Basics</a></li>
            </ul>
        </div>
        <div class="date-input flex-grow-1">
            <label for="date" class="form-label">Date</label>
            <input type="date" class="form-control" id="calendar-input-students-tab" placeholder="Y-M-d" />
        </div>
    </div>


    <div class="table-responsive">
        <table class="table students-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Registration Date</th>
                    <th>Course Taken</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="student-info d-flex align-items-center">
                            <img src="{{ asset('assets/images/ourteam3-150x150-1.jpg.png') }}" alt="Profile Picture"
                                class="profile-pic me-3">
                            <div>
                                <strong>Bilal Khalid</strong><br>
                                <small class="text-muted">bilal.k@freedomhouserecovery.org</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">2023-12-19</td>
                    <td class="text-center">2</td>
                    <td><button class="button-secondary">Details</button></td>
                </tr>
                <tr>
                    <td>
                        <div class="student-info d-flex align-items-center">
                            <img src="{{ asset('assets/images/ourteam3-150x150-1.jpg.png') }}" alt="Profile Picture"
                                class="profile-pic me-3">
                            <div>
                                <strong>Bilal Khalid</strong><br>
                                <small class="text-muted">bilal.k@freedomhouserecovery.org</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">2023-12-19</td>
                    <td class="text-center">2</td>
                    <td><button class="button-secondary">Details</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@script
    <script>
        flatpickr("#calendar-input-students-tab", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            defaultDate: new Date(),
        });
    </script>
@endscript
