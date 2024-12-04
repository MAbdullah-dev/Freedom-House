<section class="login">
    <div class="container-fluid">
        <h1>Login</h1>
        <form wire:submit.prevent="login" class="form position-relative w-50">
            <div class="login_form">
                <div wire:loading wire:target="login" class="loading-spinner">
                    <div class="spinner"></div>
                </div>
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
                        <input wire:model="email" class="form-control mt-2" type="text" name="email">
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label for="password">Password</label>
                        <input wire:model="password" class="form-control mt-2" type="password" name="password">
                        @error('password')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="text-end mt-2 mb-4">
                            <a href="">Forgot Password?</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col d-flex flex-column py-3 gap-3">
                        <button type="submit" class="button-primary">Login</button>
                        <a href='{{ route('microsoft.redirect') }}' type="button" class="button-secondary">
                            <img src="{{ asset('assets/svgs/microsift-icon.svg') }}" alt="">
                            <span class="ms-2">Continue with Microsoft</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
