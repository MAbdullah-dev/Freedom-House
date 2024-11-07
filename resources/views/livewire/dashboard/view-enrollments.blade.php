<div class="view-enrollments px-4">
    <section class="enrollments my-5 mx-4">
        <div class="tutor-avatar d-flex align-items-center gap-2 mb-5">
            <img src="{{ asset('assets/images/enrollment-profile.png') }}" alt="tutor" />
            <h6>M Abdullah</h6>
        </div>
        <div class="row">
            <div class="col-xl-6 mb-3">
                <input type="text" class="mb-3" id="calendar-input" placeholder="Select date" />
            </div>
            <div class="col-xl-3 mb-3">
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
            <div class="col-xl-3 mb-3">
                <button type="button" class="button-primary w-100" data-bs-toggle="modal" data-bs-target="#create-class"><i
                        class="fa-solid fa-plus"></i> New Event</button>
            </div>
        </div>
        <div class="no-enrollment-img d-flex justify-content-center align-content-center mt-5">
            <img src="{{ asset('assets/images/no-enrollment.png') }}" alt="no-enrollment" />
        </div>
    </section>
    <!-- Bootstrap Modal for Form -->
    <livewire:dashboard.class-form />
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
        </script>
    @endscript
