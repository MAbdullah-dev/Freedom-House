<section class="register">
    <div class="container-fluid">
        <h1>Create an account</h1>
        <p class="auth-lead text-center">Register with your name, email, and a password.</p>

        <form wire:submit.prevent="register" style="position: relative" class="form w-75 w-lg-50 w-md-75 w-sm-100 mx-auto">
            <div wire:loading wire:target="register" class="loading-spinner">
                <div class="spinner"></div>
            </div>
            <div class="register_form">
                <h6 class="text-center">Your Information</h6>
                <div class="row pt-3">
                    <div class="col-12 col-md-6">
                        <label for="first_name">First name</label>
                        <input id="first_name" wire:model="first_name" class="form-control" type="text" name="first_name" autocomplete="given-name" required>
                        @error('first_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="last_name">Last name</label>
                        <input id="last_name" wire:model="last_name" class="form-control" type="text" name="last_name" autocomplete="family-name" required>
                        @error('last_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-12 col-md-6">
                        <label for="register-email">Email</label>
                        <input id="register-email" wire:model="email" class="form-control" type="email" name="email" autocomplete="email" required>
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="phone">Phone</label>
                        <input id="phone" wire:model="phone" class="form-control" type="tel" name="phone" autocomplete="tel">
                        @error('phone')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-12 col-md-6">
                        <label for="register-password">Password</label>
                        <input id="register-password" wire:model="password" class="form-control" type="password" name="password" autocomplete="new-password" required>
                        @error('password')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="confirm_password">Confirm password</label>
                        <input id="confirm_password" wire:model="confirm_password" class="form-control" type="password"
                            name="confirm_password" autocomplete="new-password" required>
                        @error('confirm_password')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-12">
                        <div>
                            <p class="mb-0">Your account is created with the learner role.</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 d-flex flex-column py-3 gap-3">
                        <button type="submit" class="button-primary">Register</button>

                        <a type="button" href='{{ route('microsoft.redirect') }}'
                            class="button-secondary d-flex align-items-center justify-content-center">
                            <img src="{{ asset('assets/svgs/microsift-icon.svg') }}" alt="">
                            <span class="ms-2">Sign in with Microsoft</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
