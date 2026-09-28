<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Recipient;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', ['totalRecipients' => Recipient::count(), 'sentEmails' => Campaign::sum('total_sent'), 'recentCampaigns' => Campaign::latest()->limit(5)->get()]);
    }
}
