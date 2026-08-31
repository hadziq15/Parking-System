<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $logs = Log::query()
            ->with('user')
            ->where('user_id', $user?->id)
            ->latest()
            ->get();

        return view('logs.index', compact('logs'));
    }

    public function adminIndex(): View
    {
        $logs = Log::query()
            ->with('user')
            ->latest()
            ->get();

        return view('logs.admin', compact('logs'));
    }
}
