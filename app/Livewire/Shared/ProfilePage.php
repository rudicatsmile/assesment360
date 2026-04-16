<?php

namespace App\Livewire\Shared;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfilePage extends Component
{
    public function render()
    {
        $role = Auth::user()?->role;
        $layout = $role === 'admin' ? 'layouts.admin' : 'layouts.evaluator';

        return view('livewire.shared.profile-page')->layout($layout);
    }
}
