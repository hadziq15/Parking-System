<?php

/*
 * Catatan pembelajaran
 * Controller ini menangani proses edit profil pengguna seperti update data akun dan perubahan password sesuai autentikasi Laravel.
 * Prinsip umum: request -> validasi -> model -> response.
 */


namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Menampilkan form profil user.
     *
     * Fungsi edit memuat data user yang sedang login supaya form profil dapat menampilkan
     * nama, email, dan informasi akun yang relevan. Ini adalah halaman untuk mengubah data
     * diri sendiri, bukan data user lain.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Memperbarui data profil user.
     *
     * Fungsi update menerima request dari form profile. Jika email berubah, sistem akan
     * mengatur ulang email_verified_at karena email perlu diverifikasi ulang. Setelah data
     * disimpan, user akan dikembalikan ke halaman profil dengan status update.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Log::create([
            'user_id' => $user->id,
            'action' => 'Profile diperbarui: '.$user->name.' ('.$user->email.')',
        ]);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Menghapus akun user yang sedang login.
     *
     * Fungsi destroy memvalidasi password saat ini agar proses penghapusan akun aman.
     * Setelah logout dan log disimpan, akun akan dihapus dari database. Ini adalah aksi
     * sensitif yang harus melalui konfirmasi password.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        Log::create([
            'user_id' => $user->id,
            'action' => 'Profile akun dihapus: '.$user->name.' ('.$user->email.')',
        ]);

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
