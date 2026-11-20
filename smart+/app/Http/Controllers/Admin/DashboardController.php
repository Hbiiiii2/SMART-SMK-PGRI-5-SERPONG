<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $viewData = CacheService::dashboardMonitoring($today);

        return view('pages.admin.dashboard', array_merge($viewData, [
            'title' => 'Dashboard Admin',
        ]));
    }
}
