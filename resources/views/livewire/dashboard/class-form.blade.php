<!-- Modal -->
<div class="modal fade" id="create-class" tabindex="-1" aria-labelledby="createClassLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Make modal larger if needed -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createClassLabel">Create Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" x-data="{ tab: 'details', classType: '', isPaid: '' }">

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link" :class="{ 'active': tab === 'details' }" @click="tab = 'details'">Details</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ 'active': tab === 'schedule' }" @click="tab = 'schedule'">Schedule</a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-4">

                    <!-- Details Tab -->
                    <div x-show="tab === 'details'">
                        <div class="mb-3">
                            <label for="course" class="form-label">Course Name</label>
                            <select id="course" class="form-select">
                                <option value="">Select Course</option>
                                <option value="course1">Course 1</option>
                                <option value="course2">Course 2</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="classTitle" class="form-label">Class Title</label>
                            <input type="text" id="classTitle" class="form-control" placeholder="Enter Class Title">
                        </div>

                        <div class="mb-3">
                            <label for="classDescription" class="form-label">Class Description</label>
                            <textarea id="classDescription" class="form-control" placeholder="Enter Class Description"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="classType" class="form-label">Class Type</label>
                            <select id="classType" x-model="classType" class="form-select">
                                <option value="">Select Type</option>
                                <option value="virtual">Virtual</option>
                                <option value="recorded">Recorded</option>
                                <option value="onsite">Onsite</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="isPaid" class="form-label">Free or Paid</label>
                            <select id="isPaid" x-model="isPaid" class="form-select">
                                <option value="free">Free</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>

                        <!-- Show Price Input if Paid -->
                        <div class="mb-3" x-show="isPaid === 'paid'">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" id="price" class="form-control" placeholder="Enter Price">
                        </div>

                        <button type="button" class="btn btn-primary mt-3" @click="tab = 'schedule'">Next</button>
                    </div>

                    <!-- Schedule Tab -->
                    <div x-show="tab === 'schedule'">
                        <div class="mb-3">
                            <label for="startDateTime" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" id="startDateTime" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="endDateTime" class="form-label">End Date & Time</label>
                            <input type="datetime-local" id="endDateTime" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="maxStudents" class="form-label">Maximum Students</label>
                            <input type="number" id="maxStudents" class="form-control" placeholder="Enter Max Students">
                            <small class="form-text text-muted">Leave blank for no limit.</small>
                        </div>

                        <!-- Recorded Class Fields -->
                        <template x-if="classType === 'recorded'">
                            <div>
                                <div class="mb-3">
                                    <label for="videoLink" class="form-label">Video Link</label>
                                    <input type="url" id="videoLink" class="form-control" placeholder="Enter Video Link">
                                </div>
                                <div class="mb-3">
                                    <label for="quizLink" class="form-label">Quiz Link (Optional)</label>
                                    <input type="url" id="quizLink" class="form-control" placeholder="Enter Quiz Link">
                                </div>
                                <div class="mb-3">
                                    <label for="assignmentLink" class="form-label">Assignment Link (Optional)</label>
                                    <input type="url" id="assignmentLink" class="form-control" placeholder="Enter Assignment Link">
                                </div>
                            </div>
                        </template>

                        <!-- Virtual Class Field -->
                        <template x-if="classType === 'virtual'">
                            <div class="mb-3">
                                <label for="teamsLink" class="form-label">Teams Link</label>
                                <input type="url" id="teamsLink" class="form-control" placeholder="Enter Teams Link or Auto-generate">
                            </div>
                        </template>

                        <!-- Onsite Class Field -->
                        <template x-if="classType === 'onsite'">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" id="address" class="form-control" placeholder="Enter Address">
                            </div>
                        </template>

                        <button type="button" class="btn btn-secondary mt-3" @click="tab = 'details'">Back</button>
                        <button type="button" class="btn btn-primary mt-3">Save</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


