<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Visit;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->startOfDay();
        return view('dashboard', [
            'todayVisits' => Visit::where('check_in_at', '>=', $today)->count(),
            'insideNow' => Visit::whereNull('check_out_at')->count(),
            'employees' => Employee::where('is_active', true)->count(),
            'companies' => Company::where('is_active', true)->count(),
            'recentVisits' => Visit::with('employee.company')->latest('check_in_at')->limit(8)->get(),
        ]);
    }
}
