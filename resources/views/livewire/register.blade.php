<section class="register">
    <div class="container-fluid">
        <h1>REGISTER TODAY</h1>
        <div class="register_text text-center mb-4">
            <h2>SIGN UP & START LEARNING</h2>
            <p>We have the best specialists in your region. Quality and professionalism is our slogan</p>
        </div>

        <form action="" class="form w-75 w-lg-50 w-md-75 w-sm-100 mx-auto">
            <div class="register_form">
                <h6 class="text-center">Your Information</h6>

                <div class="row pt-3">
                    <div class="col-12 col-md-6">
                        <label for="first_name">First Name</label>
                        <input class="form-control" type="text" name="first_name" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="last_name">Last Name</label>
                        <input class="form-control" type="text" name="last_name" required>
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-12 col-md-6">
                        <label for="email">Email</label>
                        <input class="form-control" type="email" name="email" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="phone">Phone</label>
                        <input class="form-control" type="tel" name="phone" required>
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-12 col-md-6">
                        <label for="password">Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="confirm_password">Re-type Password</label>
                        <input class="form-control" type="password" name="confirm_password" required>
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-12">
                        <label for="role">Select Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option selected disabled>Choose...</option>
                            <option value="2">Instructor</option>
                            <option value="1">Learner</option>
                        </select>
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="terms" required>
                            <label class="form-check-label ms-2">I agree with the Terms and Conditions</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 d-flex flex-column py-3 gap-3">
                        <button type="submit" class="button-primary">Register</button>

                        <button type="button"
                            class="button-secondary d-flex align-items-center justify-content-center">
                            <img src="{{ asset('assets/svgs/microsift-icon.svg') }}" alt="">
                            <span class="ms-2">Sign in with Microsoft</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
