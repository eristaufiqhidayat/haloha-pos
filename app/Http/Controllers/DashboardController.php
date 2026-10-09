<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(ReportService $reports)
    {
        $date = now()->toDateString();
        $report = $reports->sales($date);
        $lowStock = Product::with('category')->where('is_active', true)->whereColumn('stock', '<=', 'minimum_stock')->get();

        $pending = Sale::with('items', 'user')->where('status', 'pending')->when(! auth()->user()->role->is_system, fn ($q) => $q->where('user_id', auth()->id()))->orderBy('created_at')->get();
        return view('dashboard', compact('report', 'lowStock', 'date', 'pending'));
    }
}
