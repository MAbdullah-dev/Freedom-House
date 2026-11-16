<section class="login">
    <div class="container">
        <h1>Sign in</h1>
        <p class="auth-lead">Use the email and password for your Stellix Soft LMS account.</p>
        <form wire:submit.prevent="login" class="form position-relative">
            <div class="login_form">
                <div wire:loading wire:target="login" class="loading-spinner">
                    <div class="spinner"></div>
                </div>
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->has('msg'))
                    <div class="alert alert-warning">
                        {{ $errors->first('msg') }}
                    </div>
                @endif





                @if (session()->has('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="row pt-3 mb-2">
                    <div class="col mt-4">
                        <label for="email">Email</label>
                        <input id="email" wire:model="email" class="form-control mt-2" type="email" name="email"
                            autocomplete="username" required>
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input id="password" wire:model="password" class="form-control mt-2" type="password"
                                name="password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" data-target="password"
                                aria-controls="password" aria-pressed="false">Show password</button>
                        </div>
                        @error('password')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="text-end mt-2 mb-4">
                            <button type="button" class="btn btn-link forgot-btn" data-bs-toggle="modal"
                                data-bs-target="#forgotPasswordModal">
                                Forgot Password?
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col d-flex flex-column py-3 gap-3">
                        <button type="submit" class="button-primary">Login</button>
                        <a href="{{ route('microsoft.redirect') }}" class="button-secondary text-center">
                            <img src="{{ asset('assets/svgs/microsift-icon.svg') }}" alt="">
                            <span class="ms-2">Continue with Microsoft</span>
                        </a>
                        <a href="{{ route('register') }}" wire:navigate>Create an account</a>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <!-- Forgot Password Modal -->
    <div wire:ignore.self class="modal fade" id="forgotPasswordModal" tabindex="-1"
        aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="forgotPasswordModalLabel">Forgot Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="sendPasswordResetLink">
                        <div class="mb-3">
                            <label for="reset_email" class="form-label">Enter your email address</label>
                            <input type="email" wire:model="reset_email" class="form-control" id="reset_email"
                                required>
                            @error('reset_email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn button-primary">Send Reset Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


</section>

@script
    <script>
        window.addEventListener('close-modal', event => {

            $('#forgotPasswordModal').modal('hide');
        })

        document.querySelectorAll('.password-toggle').forEach((button) => {
            if (button.dataset.bound === '1') {
                return;
            }
            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                button.textContent = showing ? 'Show password' : 'Hide password';
                button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            });
        });
    </script>
@endscript
