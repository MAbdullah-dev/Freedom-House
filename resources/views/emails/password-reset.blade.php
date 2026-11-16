<x-layouts.app>
<section class="reset-password">
    <div class="container">
        <h1>Choose a new password</h1>
        <p class="auth-lead">Enter the email address on the account and a new password.</p>
        <form method="POST" action="{{ route('password.update') }}" class="form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $email ?? '') }}"
                    autocomplete="username" required>
                @error('email')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">New password</label>
                <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" required>
                @error('password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                    autocomplete="new-password" required>
            </div>

            <button type="submit" class="button-primary">Reset password</button>
        </form>
    </div>
</section>
</x-layouts.app>
