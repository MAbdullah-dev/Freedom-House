<div>
    <section class="event-calendar my-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-9">
                    <div id="calendar"></div>
                </div>
                <div class="col-lg-3">
                    <div class="event-cards">
                        <input type="text" class="form-control mb-4" placeholder="Search">
                        @foreach ($events as $event)
                            <div class="event-card mb-3">
                                <div class="event-card-wrapper">
                                    <div class="date-badge me-3">
                                        <div class="month">{{ \Carbon\Carbon::parse($event['start'])->format('M') }}
                                        </div>
                                        <div class="day py-1">{{ \Carbon\Carbon::parse($event['start'])->format('d') }}
                                        </div>
                                        <div class="event-count mt-1 text-secondary">
                                            <i class="fas fa-user me-1"></i>{{ $event['spotsLeft'] }}
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <h6 class="mb-1 event-title">{{ $event['title'] }}</h6>
                                        <span class="badge">Free</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Date:</strong> <span id="modalDate"></span></p>
                    <p><strong>Time:</strong> <span id="modalTime"></span></p>
                    <p><strong>Spots Left:</strong> <span id="modalSpotsLeft"></span></p>
                    <p><strong>Description:</strong> <span id="modalDescription"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary">Register</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
