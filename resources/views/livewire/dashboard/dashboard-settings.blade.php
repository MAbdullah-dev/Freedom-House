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
                    <script>
                        tinymce.init({
                          selector: '#bio',
                          plugins: [
                            // Core editing features
                            'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
                            // Your account includes a free trial of TinyMCE premium features
                            // Try the most popular premium features until Nov 11, 2024:
                            'checklist', 'mediaembed', 'casechange', 'export', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage', 'advtemplate', 'ai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown',
                            // Early access to document converters
                            'importword', 'exportword', 'exportpdf'
                          ],
                          toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
                          tinycomments_mode: 'embedded',
                          tinycomments_author: 'Author name',
                          mergetags_list: [
                            { value: 'First.Name', title: 'First Name' },
                            { value: 'Email', title: 'Email' },
                          ],
                          ai_request: (request, respondWith) => respondWith.string(() => Promise.reject('See docs to implement AI Assistant')),
                          exportpdf_converter_options: { 'format': 'Letter', 'margin_top': '1in', 'margin_right': '1in', 'margin_bottom': '1in', 'margin_left': '1in' },
                          exportword_converter_options: { 'document': { 'size': 'Letter' } },
                          importword_converter_options: { 'formatting': { 'styles': 'inline', 'resets': 'inline',	'defaults': 'inline', } },
                        });
                      </script>

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
        <div id="password" class="tab-pane fade password" role="tabpanel" aria-labelledby="password-tab">
            <form action="">
                <div class="row">
                    <div class="form-group">
                        <label for="currentPassword">current password</label>
                        <input type="password" id="currentPassword" class="form-control" placeholder="current password">
                    </div>
                    <div class="form-group">
                        <label for="currentPassword">New password</label>
                        <input type="password" id="currentPassword" class="form-control" placeholder="new password">
                    </div>
                    <div class="form-group">
                        <label for="currentPassword">current password</label>
                        <input type="password" id="currentPassword" class="form-control" placeholder="confirm new password">
                    </div>
                </div>
                <input type="submit" class="button-primary" value="Reset Password">
            </form>
        </div>

        <!-- Social Profile Tab -->
        <div id="social-profile" class="social-profile tab-pane fade" role="tabpanel" aria-labelledby="social-profile-tab">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="facebook" class="d-flex align-items-center">
                            <i class="fa-brands fa-facebook-f me-2"></i> Facebook
                        </label>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="url" class="form-control" id="facebook" placeholder="http://facebook.com/username">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="twitter" class="d-flex align-items-center">
                            <i class="fa-brands fa-twitter me-2"></i> Twitter
                        </label>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="url" class="form-control" id="twitter" placeholder="http://twitter.com/username">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="linkedin" class="d-flex align-items-center">
                            <i class="fa-brands fa-linkedin me-2"></i> Linkedin
                        </label>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="url" class="form-control" id="linkedin" placeholder="http://linkedin.com/username">
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
                        <input type="url" class="form-control" id="github" placeholder="http://github.com/username">
                    </div>
                </div>
        </div>

    </div>
</div>
<!-- Place the first <script> tag in your HTML's <head> -->


    <!-- Place the following <script> and <textarea> tags your HTML's <body> -->
