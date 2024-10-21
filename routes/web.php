<?php

use App\Livewire\CourseCatelogue;
use App\Livewire\AboutUs;
use App\Livewire\CourseDetails;
use App\Livewire\Home as LivewireHome;
use App\Livewire\Login;
use App\Livewire\Register;
use Illuminate\Support\Facades\Route; // Use correct Route facade

Route::get('/', LivewireHome::class)->name('home');
Route::get('/register', Register::class)->name('register');
Route::get('/course/catalogue', CourseCatelogue::class)->name('course.catalogue');
Route::get('/login', Login::class)->name('login');
Route::get('/about-us', AboutUs::class)->name('about-us');
Route::get('/course/details', CourseDetails::class)->name('course.details');

