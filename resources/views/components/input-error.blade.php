{{--
    Catatan pembelajaran:
    Komponen ini menampilkan pesan error validasi di bawah input form.
    Struktur dasar view: menerima data, menampilkan HTML, lalu menyisipkan interaksi JavaScript jika diperlukan.
--}}

@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
