<?php

use App\Livewire\CourseCatelogue;
use App\Livewire\AboutUs;
use App\Livewire\CourseDetails;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Dashboard\DashboardEnrolledCourses;
use App\Livewire\Dashboard\DashboardProfile;
use App\Livewire\Dashboard\DashboardSettings;
use App\Livewire\Dashboard\DashboardWishlist;
use App\Livewire\Home as LivewireHome;
use App\Livewire\Login;
use App\Livewire\Register;
use App\Livewire\TutorPrfile;
use App\Livewire\OnSiteCourseDetails;
use Illuminate\Support\Facades\Route; 


Route::get('/', LivewireHome::class)->name('home');
Route::get('/register', Register::class)->name('register');
Route::get('/course/catalogue', CourseCatelogue::class)->name('course.catalogue');
Route::get('/login', Login::class)->name('login');
Route::get('/about-us', AboutUs::class)->name('about-us');
Route::get('/tutor/profile',TutorPrfile::class)->name('tutor.profile');
Route::get('/course/details', CourseDetails::class)->name('course.details');
Route::get('/on-site_course/details', OnSiteCourseDetails::class)->name('onsite.course.details');
// dashboard routes
Route::get('/dashboard',Dashboard::class)->name('dashboard');
Route::get('/dashboard/profile',DashboardProfile::class)->name('dashboard.profile');
Route::get('/dashboard/wishlist',DashboardWishlist::class)->name('dashboard.wishlist');
Route::get('/dashboard/enrolled-courses',DashboardEnrolledCourses::class)->name('dashboard.enrolledCourses');
Route::get('/dashboard/settings',DashboardSettings::class)->name('dashboard.settings');


