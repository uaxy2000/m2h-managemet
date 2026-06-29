<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Task;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalLeads = Lead::count();
        $openTasks  = Task::where('is_done', false)->count();

        return view('dashboard', compact('totalLeads', 'openTasks'));
    }
}
