<?php
// dd(request()->all());
?>
<div class="settings px-5 mb-5">
    <h2 class="my-4">Settings</h2>
    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab"
                aria-controls="profile" aria-selected="true">Profile</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="password-tab" data-bs-toggle="tab" href="#password" role="tab"
                aria-controls="password" aria-selected="false">Password</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="social-profile-tab" data-bs-toggle="tab" href="#social-profile" role="tab"
                aria-controls="social-profile" aria-selected="false">Social Profile</a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Profile Tab -->

        <div id="profile" class="tab-pane fade show active" role="tabpanel" aria-labelledby="profile-tab">
            {{-- Cover Photo Section --}}
            <div class="wrapper position-relative">
                <div class="cover-photo-wrapper">
                    @if ($coverPhoto)
                        <img src="{{ $coverPhoto->temporaryUrl() }}" class="cover-photo" alt="Cover Photo">
                    @elseif ($user->cover_photo)
                        <img src="{{ asset('storage/' . $user->cover_photo) }}" class="cover-photo" alt="Cover Photo">
                    @else
                        <img src="{{ asset('assets/images/dummy-cover-photo.jpg') }}" class="cover-photo"
                            alt="Cover Photo">
                    @endif
                    <button type="button" wire:click="deleteCoverPhoto" class="btn btn-danger btn-sm position-absolute"
                        style="top: 10px; right: 10px;">
                        <i class="fas fa-trash"></i>
                    </button>
                    <label for="coverPhotoUpload" class="btn btn-success btn-sm position-absolute"
                        style="bottom: 10px; right: 10px;">
                        <i class="fas fa-upload"></i> <span class="update-label
                        ">Update Cover
                            Photo</span>
                    </label>
                    <input type="file" id="coverPhotoUpload" wire:model="coverPhoto" class="file-input d-none"
                        accept="image/*">
                </div>

                <div class="profile-picture">
                    <div class="profile-img">
                        @if ($profilePhoto)
                            <img src="{{ $profilePhoto->temporaryUrl() }}" class="profile-photo rounded-circle"
                                alt="Profile Photo">
                        @elseif ($user->pfp)
                            <img src="{{ asset('storage/' . $user->pfp) }}" class="profile-photo rounded-circle"
                                alt="Profile Photo">
                        @else
                            <img src="{{ asset('assets/images/dummy-profile-photo.png') }}"
                                class="profile-photo rounded-circle" alt="Profile Photo">
                        @endif
                        <button class="btn btn-light btn-circle" id="profileDropdown" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="fas fa-camera"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li>
                                <label for="profilePhotoUpload" class="dropdown-item">
                                    <i class="fas fa-upload"></i> Upload Photo
                                </label>
                                <input type="file" id="profilePhotoUpload" wire:model="profilePhoto"
                                    class="file-input d-none" accept="image/*">
                            </li>
                            <li>
                                <button type="button" wire:click="deleteProfilePhoto"
                                    class="dropdown-item text-danger">
                                    <i class="fas fa-trash"></i> Delete Photo
                                </button>
                            </li>
                        </ul>
                    </div>
                    {{-- </div> --}}
                </div>
                @error('profilePhoto')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <!-- Update Profile Form -->
            <form wire:submit.prevent="updateProfile">
                <div class="form-row">
                    <div class="col-md-6">
                        <label for="firstName">First Name</label>
                        <input type="text" class="form-control" id="firstName" wire:model="firstName">
                    </div>
                    <div class="col-md-6">
                        <label for="lastName">Last Name</label>
                        <input type="text" class="form-control" id="lastName" wire:model="lastName">
                    </div>
                </div>

                <div class="form-row mt-3">
                    <div class="col-md-6">
                        <label for="userName">User Name</label>
                        <input type="text" class="form-control" id="userName" wire:model="userName">
                    </div>
                    <div class="col-md-6">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control" id="phone" wire:model="phone">
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label for="bio">Bio</label>
                    <textarea id="bio" class="form-control" wire:model="bio"></textarea>
                </div>
                <button type="submit" class="btn button-primary mt-3">Update Profile</button>
            </form>
        </div>

        <!-- Password Tab -->
        <div id="password" class="tab-pane fade password" role="tabpanel" aria-labelledby="password-tab">
            <form action="">
                <div class="row">
                    <div class="form-group">
                        <label for="currentPassword">current password</label>
                        <input type="password" id="currentPassword" class="form-control"
                            placeholder="current password">
                    </div>
                    <div class="form-group">
                        <label for="currentPassword">New password</label>
                        <input type="password" id="currentPassword" class="form-control" placeholder="new password">
                    </div>
                    <div class="form-group">
                        <label for="currentPassword">current password</label>
                        <input type="password" id="currentPassword" class="form-control"
                            placeholder="confirm new password">
                    </div>
                </div>
                <input type="submit" class="button-primary" value="Reset Password">
            </form>
        </div>

        <!-- Social Profile Tab -->
        <div id="social-profile" class="social-profile tab-pane fade" role="tabpanel"
            aria-labelledby="social-profile-tab">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="facebook" class="d-flex align-items-center">
                        <i class="fa-brands fa-facebook-f me-2"></i> Facebook
                    </label>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="url" class="form-control" id="facebook"
                        placeholder="http://facebook.com/username">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="twitter" class="d-flex align-items-center">
                        <i class="fa-brands fa-twitter me-2"></i> Twitter
                    </label>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="url" class="form-control" id="twitter"
                        placeholder="http://twitter.com/username">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="linkedin" class="d-flex align-items-center">
                        <i class="fa-brands fa-linkedin me-2"></i> Linkedin
                    </label>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="url" class="form-control" id="linkedin"
                        placeholder="http://linkedin.com/username">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="website" class="d-flex align-items-center">
                        <i class="fa-solid fa-globe me-2"></i> Website
                    </label>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="url" class="form-control" id="website" placeholder="http://example.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="github" class="d-flex align-items-center">
                        <i class="fa-brands fa-github me-2"></i> Github
                    </label>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="url" class="form-control" id="github"
                        placeholder="http://github.com/username">
                </div>
            </div>
        </div>

    </div>
</div>
