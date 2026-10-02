<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(ReportService $reports)
    {
        $date = now()->toDateString();
        $report = $reports->sales($date);
        $lowStock = Product::with('category')->where('is_active', true)->whereColumn('stock', '<=', 'minimum_stock')->get();

        return view('dashboard', compact('report', 'lowStock', 'date'));
    }
}
