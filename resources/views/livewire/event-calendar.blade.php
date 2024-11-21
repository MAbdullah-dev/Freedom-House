<div>

    <section class="event-calendar my-5">
        <div class="container">


            <!-- Calendar and Event Cards -->
            <div class="row">
                <div class="col-lg-9">
                    <div id="calendar"></div>
                </div>
                <div class="col-lg-3">
                    <div class="event-cards">
                        <input type="text" class="form-control mb-4" placeholder="Search">
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
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
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
    </div>
</div>
