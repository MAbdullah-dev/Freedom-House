<div class="view-enrollments py-5 px-4">
    <section class="enrollments mx-4">
        <div class="tutor-avatar d-flex align-items-center gap-2 mb-5">
            <img src="{{ asset('storage/' . Auth::user()->pfp) }}" alt="tutor" />
            <h6>M Abdullah</h6>
        </div>
        <div class="row">
            <div class="col-xl-6 mb-3" wire:ignore>
                <input type="text" class="mb-3" id="calendar-input" placeholder="Select date" />
            </div>
            <div class="col-xl-3 mb-3">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button"
                        id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-globe icon"></i>
                        <span id="selected-timezone">-04:00</span>
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
            @if (Auth::user()->role_id == '2' || Auth::user()->role_id == '1')
                <div class="col-xl-3 mb-3">
                    <a href="{{ route('dashboard.create.class') }}" type="button" class="button-primary w-100">
                        <i class="fa-solid fa-plus"></i> New
                        Class/Event
                    </a>
                </div>
            @endif
        </div>
    </section>
    <section class="enrollments my-5 mx-4">
        <section class="classes my-5">
            @if (collect($items)->isNotEmpty())
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>{{ $item['title'] }}</td>
                                    <td>{{ $item['type'] }}</td>
                                    <td>{{ $item['date'] }}</td>
                                    <td>{{ $item['time'] }}</td>
                                    <td>{{ $item['price'] }}</td>
                                    <td>
                                        <button class="button-primary"
                                            wire:click="selectItem({{ $item['id'] }}, '{{ $item['model_type'] }}')"
                                            data-bs-toggle="modal" data-bs-target="#viewItemModal">
                                            View
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="no-enrollment-img d-flex justify-content-center align-content-center mt-5">
                    <img src="{{ asset('assets/images/no-enrollment.png') }}" alt="no-enrollment" />
                </div>
            @endif
        </section>
        <!-- Modal -->
        <div wire:ignore.self class="modal fade modal-lg" id="viewItemModal" tabindex="-1"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">
                            @if ($selectedClass)
                                Class Details
                            @elseif ($selectedEvent)
                                Event Details
                            @else
                                Details
                            @endif
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div wire:loading>
                            <p>Loading details...</p>
                        </div>
                        <div class="enrollment-modal-details" wire:loading.remove>
                            @if ($selectedClass)
                                <!-- Class Details -->
                                <p><strong>Title:</strong> {{ $selectedClass->title }}</p>
                                <p><strong>Description:</strong> {{ $selectedClass->description }}</p>
                                <p><strong>Capacity:</strong> {{ $selectedClass->capacity }}</p>
                                <p><strong>Class Type:</strong> {{ ucfirst($selectedClass->class_type) }}</p>
                                <p><strong>Class Date:</strong>
                                    {{ $selectedClass->class_date ? \Carbon\Carbon::parse($selectedClass->class_date)->format('F, d Y') : 'Not Set' }}
                                </p>
                                <p><strong>Class Time:</strong> {{ $selectedClass->class_time ?? 'Not Set' }}</p>
                                <p><strong>Student Capacity:</strong> {{ $selectedClass->capacity }}</p>
                                @if ($selectedClass->class_type == 'recorded')
                                    <p><strong>Video Link:</strong> <a href="{{ $selectedClass->recorded_video_url }}"
                                            target="_blank">{{ $selectedClass->recorded_video_url }}</a></p>
                                @elseif ($selectedClass->class_type == 'onsite')
                                    <p><strong>Location:</strong> {{ $selectedClass->onsite_address }}</p>
                                @else
                                    <p><strong>Virtual:</strong> <a href="{{ $selectedClass->teams_link }}"
                                            target="_blank">Join Via Teams</a></p>
                                @endif
                                <p><strong>Booking Start Date:</strong> {{ $selectedClass->booking_start_date }}</p>
                                <p><strong>Booking End Date:</strong> {{ $selectedClass->booking_end_date }}</p>
                                <p><strong>Price:</strong>
                                    {{ $selectedClass->price ? '$' . number_format($selectedClass->price, 2) : 'Free' }}
                                </p>
                            @elseif ($selectedEvent)
                                <!-- Event Details -->
                                <p><strong>Title:</strong> {{ $selectedEvent->title }}</p>
                                <p><strong>Description:</strong> {{ $selectedEvent->description }}</p>
                                <p><strong>Event Date:</strong>
                                    {{ $selectedEvent->event_date ? \Carbon\Carbon::parse($selectedEvent->event_date)->format('F, d Y') : 'Not Set' }}
                                </p>
                                <p><strong>Event Time:</strong> {{ $selectedEvent->event_time ?? 'Not Set' }}</p>
                                <p><strong>Location:</strong> {{ $selectedEvent->location ?? 'Not Set' }}</p>
                                <p><strong>Price:</strong>
                                    {{ $selectedEvent->price ? '$' . number_format($selectedEvent->price, 2) : 'Free' }}
                                </p>
                            @else
                                <p>No details available.</p>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center justify-content-between py-2">
                        <div class="icons d-flex align-items-center justify-content-center gap-3">
                            <!-- Edit Icon -->
                            @if ($selectedClass || $selectedEvent)
                                <i type="button" class="edit-icon fa-solid fa-pen-to-square" wire:click="editItem"></i>
                            @endif
                            <!-- Delete Icon -->
                            @if ($selectedClass || $selectedEvent)
                                <i type="button" class="delete-icon fa-solid fa-trash" wire:click="deleteItem"></i>
                            @endif
                        </div>

                        <!-- Create Quiz Button (for Recorded Classes) -->
                        @if ($selectedClass && $selectedClass->class_type == 'recorded')
                            <button type="button" class="button-primary" wire:click="createQuiz">Create Quiz</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
</div>
@script
    <script>
        window.addEventListener('close-modal', event => {
            $('#viewclassModal').modal('hide');
        });
        document.querySelectorAll('.dropdown-item').forEach(function(item) {
            item.addEventListener('click', function(event) {
                const selectedTimezone = event.target.textContent;

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
            onClose: function(selectedDates, dateStr, instance) {
                @this.set('dateRange', dateStr); // Update Livewire property directly
            }
        });
    </script>
@endscript
