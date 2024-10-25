<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class DashboardWishlist extends Component
{
    public function render()
    {
        return view('livewire.dashboard.dashboard-wishlist')->layout('components.layouts.dashboard');
    }
}
