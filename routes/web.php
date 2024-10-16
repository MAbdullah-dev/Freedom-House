<?php
use App\Livewire\Home as LivewireHome;
use Illuminate\Support\Facades\Route; // Use correct Route facade

Route::get('/', LivewireHome::class)->name('home');

