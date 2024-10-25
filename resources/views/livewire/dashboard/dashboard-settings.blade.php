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
            <div class="cover-photo-wrapper">
                <img src="{{ asset('assets/images/cover-photo.png') }}" class="cover-photo" alt="Cover Photo">
                <i class="fa fa-trash delete-cover-photo"></i>
            </div>
            <div class="profile-photo-wrapper">
                <img src="{{ asset('assets/images/image (1).png') }}" class="profile-photo" alt="Profile Photo">
                <i class="fa fa-trash delete-profile-photo"></i>
            </div>

            <form>
                <div class="form-row">
                    <div class="col-md-6">
                        <label for="firstName">First Name</label>
                        <input type="text" class="form-control" id="firstName">
                    </div>
                    <div class="col-md-6">
                        <label for="lastName">Last Name</label>
                        <input type="text" class="form-control" id="lastName">
                    </div>
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <label for="userName">User Name</label>
                        <input type="text" class="form-control" id="userName">
                    </div>
                    <div class="col-md-6">
                        <label for="phoneNumber">Phone Number</label>
                        <input type="text" class="form-control" id="phoneNumber">
                    </div>
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label for="displayName">Display name publicly as</label>
                    <select class="form-control" id="displayName">
                        <option>Display name publicly as</option>
                        <option>Bilal Khalid</option>
                        <option>Bilalkajaja</option>
                        <option>As Student</option>
                    </select>
                </div>
                <p class="public-name-info">
                    The display name is shown in all public fields, such as the author name, instructor name, student
                    name, and name that will be printed on the certificate.
                </p>
                <button type="submit" class="button-primary">Update Profile</button>
            </form>
        </div>

        <!-- Password Tab -->
        <div id="password" class="tab-pane fade" role="tabpanel" aria-labelledby="password-tab">
            <p>Password section</p>
        </div>

        <!-- Social Profile Tab -->
        <div id="social-profile" class="tab-pane fade" role="tabpanel" aria-labelledby="social-profile-tab">
            <p>Social profile</p>
        </div>
    </div>
</div>
