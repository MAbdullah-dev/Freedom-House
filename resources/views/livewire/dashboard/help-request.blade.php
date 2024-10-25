<div class="help-request px-4">
    <section class="help-request-alert py-5 px-5 mt-3">
        <div class="row align-items-center justify-content-center flex-wrap">
            <div class="col-md-7">
                <div class="help-request-content d-flex align-items-center justify-content-center gap-3">
                    <div class="alert-icon">
                        <i class="fa-solid fa-circle-exclamation fa-2xl" style="color: #000000;"></i>
                    </div>
                    <div class="alert-text">
                        <p>
                            Do not share sensitive information (attachments or text). Ex. Your credit card details or
                            personal ID numbers.
                        </p>
                    </div>
                </div>
                <form action="" class="mt-5 helprequest-form">
                    <label for="subject" class="mt-4 mb-3"> subject</label>
                    <input type="text" class="form-control" id="subject" name="subject"
                        placeholder="e.g I have an issue in my course video">
                    <label for="Description" class="my-3">Description</label>
                    <textarea name="Description" class="form-control" id="Description" cols="30" rows="10"></textarea>
                    <div class="custom-file-input">
                        <label for="file-upload" class="custom-file-upload button-secondary mt-3">
                            Attach File
                        </label>
                        <input id="file-upload" type="file" />
                    </div>
                    <div class="text-center">

                        <input type="submit" value="Send supportRequest" class="button-primary mt-4 ms-5">
                    </div>
                </form>
            </div>
            <div class="col-md-5">
                <div class="help-img">
                    <img class="w-100" src="{{ asset('assets/images/help-img.png') }}" alt="">
                </div>
            </div>
        </div>
    </section>
    <section class="help-request-review">
        <div class="help-request-head py-4 px-4 my-5">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="request-icon d-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-bookmark" style="color: #156636;"></i>
                        </div>
                        <div class="request-text">
                            <span class="">Support</span>
                            <h4 class="mb-0">List of Support Request Submissions</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="input-group d-flex align-items-center justify-content-end gap-2 position-relative">
                        <input type="text" class="form-control" placeholder="Search">
                        <i class="fa-solid fa-magnifying-glass position-absolute" ></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="help-request-body mb-5">
            <table class="table">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">Subject</th>
                    <th scope="col">Created</th>
                    <th scope="col">Last Activity</th>
                    <th scope="col">Status</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
        </div>
    </section>
</div>
