<div>

    <section class="event-calendar">
        <div class="container">
            <div class="calendar-header d-flex align-items-center mb-3">
                <div class="ms-auto d-flex align-items-center">
                    <input type="text" class="form-control search-bar me-2" placeholder="🔍 Search">
                    <button class="btn btn-outline-secondary btn-filter">Filters <i
                            class="fas fa-caret-down"></i></button>
                </div>
            </div>

            <!-- Additional Filters Row (shown when "Filters" is clicked) -->
            <div class="calendar-additional-filters row g-2 mb-3 collapse">
                <div class="col-md-3">
                    <select class="form-select">
                        <option>Event Type</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option>Event Status</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option>Event Employee</option>
                    </select>
                </div>
            </div>

            <!-- Calendar and Event Cards -->
            <div class="row">
                <div class="col-lg-9">
                    <div id="calendar"></div>
                </div>
                <div class="col-lg-3">
                    <div class="event-cards">
                        <div class="event-card mb-3">
                            <div class="event-card-wrapper">
                                <div class="date-badge me-3">
                                    <div class="month">OCT</div>
                                    <div class="day py-1">30</div>
                                    <div class="event-count mt-1 text-secondary"><i class="fas fa-user me-1"></i>6/20
                                    </div>

                                </div>
                                <div class="d-flex align-items-center">
                                    <h6 class="mb-1 event-title">Documentation for Authorization</h6>
                                    <span class="badge">Free</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Event Modal -->
    {{-- <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Event Details Here -->
                    <p><strong>Date:</strong> October 30, 2024</p>
                    <p><strong>Time:</strong> 10:00 AM - 11:00 AM</p>
                    <p><strong>Spots Left:</strong> 20/20</p>
                    <p><strong>Description:</strong> Full training session covering essential topics...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary">Register</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div> --}}
</div>
