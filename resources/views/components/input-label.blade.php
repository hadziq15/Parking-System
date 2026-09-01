{{--
    Catatan pembelajaran:
    Komponen label input ini memudahkan styling label form yang konsisten.
    Struktur dasar view: menerima data, menampilkan HTML, lalu menyisipkan interaksi JavaScript jika diperlukan.
--}}

@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
    {{ $value ?? $slot }}
</label>
