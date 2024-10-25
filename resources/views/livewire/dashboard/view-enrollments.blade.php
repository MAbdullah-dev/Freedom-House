<div class="view-enrollments px-4">
    <section class="enrollments my-5 mx-4">
        <div class="tutor-avator d-flex align-items-center gap-2">
            <img src="{{ asset('assets/images/enrollemnt-profile.png') }}" alt="tutor" />
            <h6>M Abdullah</h6>
        </div>
        <div class="row">
            <div class="col-md-8">
                <input type="text" class="calendar-input mb-3" id="calendar" placeholder="Select date" />
            </div>
            <div class="col-md-4">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="fas fa-globe icon"></i> -04:00
                    </button>
                    <ul class="dropdown-menu custom-dropdown-menu w-100" aria-labelledby="dropdownMenuButton">
                      <li><a class="dropdown-item" href="#"><i class="fas fa-map-marker-alt icon"></i> Africa/Abidjan</a></li>
                      <li><a class="dropdown-item" href="#"><i class="fas fa-map-marker-alt icon"></i> Africa/Accra</a></li>
                      <li><a class="dropdown-item" href="#"><i class="fas fa-map-marker-alt icon"></i> Africa/Addis_Ababa</a></li>
                      <li><a class="dropdown-item" href="#"><i class="fas fa-map-marker-alt icon"></i> Africa/Algiers</a></li>
                      <li><a class="dropdown-item" href="#"><i class="fas fa-map-marker-alt icon"></i> Africa/Asmara</a></li>
                      <li><a class="dropdown-item" href="#"><i class="fas fa-map-marker-alt icon"></i> Africa/Bamako</a></li>
                      <li><a class="dropdown-item" href="#"><i class="fas fa-map-marker-alt icon"></i> Africa/Banjul</a></li>
                    </ul>
                  </div>
            </div>
        </div>
        <div class="no-enrollment-img d-flex justify-content-center align-content-center mt-5">
            <img src="{{ asset('assets/images/no-enrollment.png') }}" alt="no-enrollment" />
        </div>

    </section>
</div>
