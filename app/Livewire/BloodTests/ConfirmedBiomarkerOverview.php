<?php

namespace App\Livewire\BloodTests;

use App\Domain\Dashboard\BuildBloodResultsOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ConfirmedBiomarkerOverview extends Component
{
    public function render(BuildBloodResultsOverview $buildBloodResultsOverview): View
    {
        return view('livewire.blood-tests.confirmed-biomarker-overview', [
            'biomarkers' => $buildBloodResultsOverview(Auth::user()),
            'measurementCount' => $buildBloodResultsOverview->measurementCount(Auth::user()),
        ]);
    }
}
