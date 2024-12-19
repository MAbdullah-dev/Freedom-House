<div>
    <section class="event-calendar my-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-9" wire:ignore>
                    <div id="calendar"></div>
                </div>
                <div class="col-lg-3">
                    <div class="event-cards">
                        <input type="text" class="form-control mb-4" placeholder="Search">
                        @foreach ($eventCards as $event)
                            <button type="button" class="event-card mb-3 btn"
                                wire:click="selectEvent({{ json_encode($event) }}, 'eventCard')" data-bs-toggle="modal"
                                data-bs-target="#eventModal">
                                <div class="event-card-wrapper">
                                    <div class="date-badge me-3">
                                        <div class="month">{{ \Carbon\Carbon::parse($event['date'])->format('M') }}
                                        </div>
                                        <div class="day py-1">{{ \Carbon\Carbon::parse($event['date'])->format('d') }}
                                        </div>
                                        <div class="event-count mt-1 text-secondary">
                                            <i class="fas fa-user me-1"></i>{{ $event->capacity . ' slots' ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <h6 class="mb-1 event-title">{{ $event['title'] ?? 'N/A' }}</h6>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- eventcard Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true"
        wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="event-modal-title">{{ $selectedEvent['title'] ?? 'Event Details' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Start Date:</strong>
                        <span>{{ \Carbon\Carbon::parse($selectedEvent['start_date'] ?? '')->toFormattedDateString() ?: 'N/A' }}</span>
                    </p>
                    <p><strong>End Date:</strong>
                        <span>{{ \Carbon\Carbon::parse($selectedEvent['end_date'] ?? '')->toFormattedDateString() ?: 'N/A' }}</span>
                    </p>
                    <p><strong>Spots Left:</strong> <span>{{ $selectedEvent['capacity'] ?? 'N/A' }}</span></p>
                    <p><strong>Description:</strong>
                        <span>{{ $selectedEvent['description'] ?? 'No description available' }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    {{-- <!-- Button trigger modal -->
    <button type="button" class="btn btn-primary d-none" data-bs-toggle="modal" data-bs-target="#exampleModal">
        Launch demo modal
    </button>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div> --}}
</div>
