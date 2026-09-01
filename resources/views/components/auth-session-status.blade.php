{{--
    Catatan pembelajaran:
    Komponen ini menampilkan pesan status autentikasi seperti kesalahan login atau status reset password.
    Struktur dasar view: menerima data, menampilkan HTML, lalu menyisipkan interaksi JavaScript jika diperlukan.
--}}

@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif
