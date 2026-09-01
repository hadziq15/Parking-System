<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Menampilkan user yang tersedia dan melakukan pencarian untuk memudahkan
     * proses pengelolaan akses aplikasi.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('management.user.index', compact('users', 'search'));
    }

    /**
     * Menambahkan user baru dengan password yang dikonversi ke hash agar aman
     * dan tidak disimpan dalam bentuk plain text.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'user', 'owner'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'User ditambahkan: '.$user->name.' ('.$user->email.')',
        ]);

        return redirect()->route('user-management.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'user', 'owner', 'super_admin'])],
        ]);

        if ($user->role === 'super_admin') {
            $validated['role'] = 'super_admin';
        } elseif ($validated['role'] === 'super_admin') {
            abort(403, 'Hanya boleh ada satu akun super admin.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'User diperbarui: '.$user->name.' ('.$user->email.')',
        ]);

        return redirect()->route('user-management.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role === 'super_admin') {
            abort(403, 'Super admin tidak bisa dihapus.');
        }

        if (Auth::id() === $user->id) {
            abort(403, 'Anda tidak bisa menghapus akun sendiri.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        $user->delete();

        Log::create([
            'user_id' => Auth::id(),
            'action' => 'User dihapus: '.$userName.' ('.$userEmail.')',
        ]);

        return redirect()->route('user-management.index')->with('success', 'User berhasil dihapus.');
    }
}
