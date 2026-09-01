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
                        <a href="{{ session('exit_ticket_url') }}" class="mt-2 inline-block font-semibold underline" target="_blank" rel="noopener noreferrer">
                            Download karcis PDF
                        </a>
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

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nomor Polisi</th>
                                <th class="px-4 py-3">Nomor Karcis</th>
                                <th class="px-4 py-3">Jenis Pelanggan</th>
                                <th class="px-4 py-3">Area</th>
                                <th class="px-4 py-3">Waktu Masuk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($activeTransactions as $transaction)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $transaction->plat_nomor }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $transaction->nomor_karcis ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $transaction->jenisPelanggan?->nama ?? 'Reguler' }}</td>
                                    <td class="px-4 py-3">{{ $transaction->areaParkir?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $transaction->waktu_masuk?->format('d M Y H:i') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada kendaraan yang sedang parkir.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
