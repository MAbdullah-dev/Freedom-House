<div class="statements-tab px-5">
    <div class="filters d-flex flex-column flex-md-row gap-3 align-items-start mb-4">
        <div class="dropdown flex-grow-1">
            <label for="courses" class="form-label">Courses</label>
            <div class="dropdown">
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
        </div>
        <div class="flex-grow-1">
            <label for="date" class="form-label">Date</label>
            <input type="date" class="form-control" id="calendar-input-statements-tab" placeholder="Y-M-d" />
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table statements-table">
            <thead>
                <tr>
                    <th>Statement Info</th>
                    <th>My Earnings</th>
                    <th>Admin Gets</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="statement-info">
                            <span class="text-muted">Wc-checkout-draft · 2024-09-18</span><br />
                            <h4>Understanding Personality Disorders</h4><br />
                            <a href="#" class="text-primary">Order ID: #1147</a>
                        </div>
                    </td>
                    <td>
                        <div class="earnings">
                            <strong>$0.00</strong><br />
                            <small class="text-muted">0.00% of $10.00</small>
                        </div>
                    </td>
                    <td>
                        <div class="admin-gets">
                            <strong>$10.00</strong><br />
                            <small class="text-muted">As per 100.00%</small>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="statement-info">
                            <span class="text-muted">Wc-checkout-draft · 2024-07-31</span><br />
                            <h4>Maintain Professional Boundaries</h4><br />
                            <a href="#" class="text-primary">Order ID: #1046</a>
                        </div>
                    </td>
                    <td>
                        <div class="earnings">
                            <strong>$0.00</strong><br />
                            <small class="text-muted">0.00% of $20.00</small>
                        </div>
                    </td>
                    <td>
                        <div class="admin-gets">
                            <strong>$20.00</strong><br />
                            <small class="text-muted">As per 100.00%</small>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@script
    <script>
        flatpickr("#calendar-input-statements-tab", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            defaultDate: new Date(),
        });
    </script>
@endscript
