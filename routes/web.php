<?php
use App\Livewire\Home as LivewireHome;
use App\Livewire\Register;
use Illuminate\Support\Facades\Route; // Use correct Route facade

Route::get('/', LivewireHome::class)->name('home');
Route::get('/register', Register::class)->name('register');

