<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingManagementController extends Controller
{
    public function index(): View
    {
        $settings = Setting::orderBy('key')->get();

        return view('management.setting.index', compact('settings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'value' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::updateOrCreate(
            ['key' => $validated['key']],
            [
                'value' => $validated['value'] ?? '',
                'description' => $validated['description'] ?? null,
            ],
        );

        return redirect()->route('management.setting.index')->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function saveBulk(Request $request): RedirectResponse
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }

        return redirect()->route('management.setting.index')->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function destroy(Setting $setting): RedirectResponse
    {
        $setting->delete();

        return redirect()->route('management.setting.index')->with('success', 'Pengaturan berhasil dihapus.');
    }
}
