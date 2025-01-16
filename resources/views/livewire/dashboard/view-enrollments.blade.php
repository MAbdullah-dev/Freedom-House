<div class="view-enrollments py-5 px-4">
    @if (Auth::user()->role_id == '2' || Auth::user()->role_id == '1')
        <section class="enrollments mx-4">
            <div class="tutor-avatar d-flex align-items-center gap-2 mb-5">
                <img src=" {{ Auth::user()->pfp ? asset('storage/' . Auth::user()->pfp) : asset('assets/images/dummy-profile-photo.webp') }}"
                    alt="tutor" />
                <h6>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}<h6>
            </div>
            <div class="row">
                <div class="col-xl-9 mb-3" wire:ignore>
                    <input type="text" class="mb-3" id="calendar-input" placeholder="Select date" />
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
        <section class="classes enrollments my-5 mx-4">
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
                                <th class="">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>{{ $item['title'] }}</td>
                                    <td>{{ $item['type'] }}</td>
                                    <td>{{ $item['date'] }}</td>
                                    <td>{{ $item['time'] }}</td>
                                    <td>
                                        @if ($item['price'] === 'Free' || $item['price'] === '$0.00')
                                            <span class="badge bg-info">Free</span>
                                        @else
                                            <span class="badge bg-success">{{ $item['price'] }}</span>
                                        @endif
                                    </td>


                                    <td>
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#attendee-modal"
                                            class="attendee-btn"><i class="fa-solid fa-user"></i></button>
                                        <button class="view-btn"
                                            wire:click="selectItem({{ $item['id'] }}, '{{ $item['model_type'] }}')"
                                            data-bs-toggle="modal" data-bs-target="#viewItemModal">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="no-enrollment-img d-flex justify-content-center align-content-center mt-5">
                    <img src="{{ asset('assets/images/no-enrollment.webp') }}" alt="no-enrollment" />
                </div>
            @endif
        </section>
    @endif
    <section class="enrollments-table my-5 mx-4">
        <h4>Enrollments</h4>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Order #</th>
                        <th>Class/Event</th>
                        <th>Payment</th>
                        <th>Time</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <!-- Order Number -->
                            <td>{{ $enrollment->order_number }}</td>

                            <!-- Class/Event Title -->
                            <td>
                                @if ($enrollment->class_id)
                                    <span class="badge bg-primary"
                                        title="Class">{{ $enrollment->class->title }}</span>
                                @elseif ($enrollment->event_id)
                                    <span class="badge bg-success"
                                        title="Event">{{ $enrollment->event->title }}</span>
                                @else
                                    <span class="text-muted">No Enrollment</span>
                                @endif
                            </td>

                            <!-- Payment Status -->
                            <td>
                                @if ($enrollment->payment_status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @else
                                    <span class="badge bg-info">Free</span>
                                @endif
                            </td>

                            <!-- Time and Date -->
                            @if ($enrollment->class_id)
                                <td>{{ \Carbon\Carbon::parse($enrollment->class->class_time, 'UTC')->setTimezone(Auth::user()->timezone)->format('g:i A') }}
                                </td>
                                <td>{{ \Carbon\Carbon::parse($enrollment->class->class_date, 'UTC')->setTimezone(Auth::user()->timezone)->format('F j, Y') }}
                                </td>
                            @elseif ($enrollment->event_id)
                                <td>{{ \Carbon\Carbon::parse($enrollment->event->class_time, 'UTC')->setTimezone(Auth::user()->timezone)->format('g:i A') }}
                                </td>
                                <td>{{ \Carbon\Carbon::parse($enrollment->event->class_date, 'UTC')->setTimezone(Auth::user()->timezone)->format('F j, Y') }}
                                </td>
                            @else
                                <td>N/A</td>
                                <td>N/A</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No enrollments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <!-- modal -->
    <div wire:ignore.self class="modal fade modal-lg" id="viewItemModal" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-white">
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
                            <p><strong>Class Time:</strong>
                                {{ $selectedClass->class_time
                                    ? \Carbon\Carbon::parse($selectedClass->class_time, 'UTC')->setTimezone(Auth::user()->timezone)->format('g:i A')
                                    : 'Not Set' }}
                            </p>
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
                                {{ $selectedEvent->class_date ? \Carbon\Carbon::parse($selectedEvent->class_date)->format('F, d Y') : 'Not Set' }}
                            </p>
                            <p><strong>Event Time:</strong>
                                {{ $selectedEvent->class_time
                                    ? \Carbon\Carbon::parse($selectedEvent->class_time, 'UTC')->setTimezone(Auth::user()->timezone)->format('g:i A')
                                    : 'Not Set' }}
                            </p>
                            @if ($selectedEvent->onsite_address)
                                <p><strong>Location:</strong> {{ $selectedEvent->onsite_address ?? 'Not Set' }}</p>
                            @elseif ($selectedEvent->teams_link)
                                <a href="{{ $selectedEvent->teams_link }}"><strong>Location: Join Via Teams</strong>
                                </a>
                            @endif
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
                            <i type="button" class="edit-icon fa-solid fa-pen-to-square" wire:click="editItem"
                                data-bs-dismiss="modal"></i>
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
    <div class="modal fade" id="attendee-modal" tabindex="-1" role="dialog" aria-labelledby="attendeeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                {{-- <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div> --}}
                <div class="modal-body p-0">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-muted py-2" id="attendeeModalLabel">Attendees</h5>
                                <button type="button" class="btn button-primary w-100 mb-2">+ Add Attendee</button>
                                <input type="search" class="form-control" placeholder="🔍 Find Attendees">
                                <div class="attendee-list">
                                    <div class="attendee py-4">
                                        <div class="row">
                                            <hr>
                                            <div class="col-8">
                                                <input class="form-control mb-3" type="text" value="Brian M">
                                                <input class="form-control mb-3" type="text" value="1234567890">
                                                <select class="form-control mb-3 text-muted">
                                                    <option value="">Free</option>
                                                    <option value="">Unpaid</option>
                                                    <option value="">Pending</option>
                                                    <option value="">Paid</option>
                                                    <option value="">Refunded</option>
                                                </select>
                                            </div>
                                            <hr>
                                            <div class="col-4 text-right">
                                                {{-- <button type="button" class="btn btn-danger">Remove Attendee</button>
                                                <button type="button" class="btn btn-primary">Edit Attendee</button> --}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('js')
    <script>
        window.addEventListener('close-modal', event => {
            $('#viewItemModal').modal('hide');
        });
    </script>
@endpush
@script
    <script>
        flatpickr("#calendar-input", {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            minDate: "2024-01-01",
            maxDate: "2030-12-31",
            onClose: function(selectedDates, dateStr, instance) {
                @this.set('dateRange', dateStr);
            }
        });
    </script>
@endscript
