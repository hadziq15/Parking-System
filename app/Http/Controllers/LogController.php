<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        $logs = Log::with('user')->latest()->get();

        return view('logs.index', compact('logs'));
    }
}
