{{--
    Catatan pembelajaran:
    View Blade ini menampilkan tampilan halaman aplikasi. Komponen utama seperti form, tabel, dan modal dipasang di sini, lalu diberi data dari controller melalui compact() atau session().
    Struktur dasar view: menerima data, menampilkan HTML, lalu menyisipkan interaksi JavaScript jika diperlukan.
--}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            {{ __('Kendaraan Keluar') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <div>{{ session('success') }}</div>
                    @if (session('exit_ticket_url'))
                        <button type="button" data-open-ticket="{{ session('exit_ticket_url') }}" class="mt-2 inline-block font-semibold underline text-emerald-700 hover:text-emerald-800">
                            Buka karcis PDF
                        </button>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                {{--
                    Form keluar digunakan untuk menutup transaksi parkir yang sedang aktif.
                    Input yang harus diisi:
                    - plat_nomor atau nomor_karcis: identitas kendaraan yang keluar.
                    - karcis_hilang: checkbox kalau karcis hilang dan perlu denda.
                    Setelah submit, controller ParkirController@storeKeluar akan menghitung durasi,
                    tarif, dan total bayar yang harus dibayarkan.
                --}}
                <form method="POST" action="{{ route('parkir.keluar.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="plat_nomor" class="mb-1 block text-sm font-medium text-slate-700">Nomor Polisi / Nomor Karcis</label>
                        <input id="plat_nomor" name="plat_nomor" type="text" value="{{ old('plat_nomor') }}"
                            placeholder="Contoh: B 1234 ABC atau KRC-240901-ABC123"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    </div>

                    <label class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        <input type="checkbox" name="karcis_hilang" value="1" {{ old('karcis_hilang') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        Karcis hilang (tambahkan denda sesuai jenis pelanggan)
                    </label>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Proses Keluar
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Lihat daftar kendaraan yang masih terparkir di halaman
                <a href="{{ route('parkir.terparkir') }}" class="font-semibold text-indigo-600 underline">Data Kendaraan Terparkir</a>.
            </div>
        </div>
    </div>

    @if (session('exit_ticket_url'))
        <div id="ticket-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="flex h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-800">Preview Karcis</h3>
                    <button type="button" id="close-ticket-modal" class="rounded-lg border border-slate-200 px-2 py-1 text-sm text-slate-600 hover:bg-slate-100">Tutup</button>
                </div>
                <iframe src="{{ session('exit_ticket_url') }}" class="h-full w-full bg-white" title="Karcis PDF"></iframe>
            </div>
        </div>
    @endif

    <script>
        const ticketModal = document.getElementById('ticket-modal');
        const closeTicketModalButton = document.getElementById('close-ticket-modal');

        if (ticketModal && closeTicketModalButton) {
            closeTicketModalButton.addEventListener('click', function () {
                ticketModal.classList.add('hidden');
            });

            ticketModal.addEventListener('click', function (event) {
                if (event.target === ticketModal) {
                    ticketModal.classList.add('hidden');
                }
            });
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-open-ticket]');
            if (!trigger) {
                return;
            }

            if (ticketModal) {
                ticketModal.classList.remove('hidden');
                const iframe = ticketModal.querySelector('iframe');
                if (iframe) {
                    iframe.src = trigger.dataset.openTicket;
                }
            } else {
                window.open(trigger.dataset.openTicket, '_blank', 'noopener,noreferrer');
            }
        });
    </script>
</x-app-layout>
