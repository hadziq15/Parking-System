{{--
    Catatan pembelajaran:
    View Blade ini menampilkan tampilan halaman aplikasi. Komponen utama seperti form, tabel, dan modal dipasang di sini, lalu diberi data dari controller melalui compact() atau session().
    Struktur dasar view: menerima data, menampilkan HTML, lalu menyisipkan interaksi JavaScript jika diperlukan.
--}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            {{ __('Kelola Kendaraan') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="{
        modalOpen: false,
        mode: 'create',
        formAction: '{{ route('management.vehicle.store') }}',
        method: 'POST',
        form: { pemilik: '', plat_nomor: '', jenis_kendaraan: 'mobil', warna: '', jenis_pelanggan_id: '' },
        openCreate() {
            this.mode = 'create';
            this.formAction = '{{ route('management.vehicle.store') }}';
            this.method = 'POST';
            this.form = { pemilik: '', plat_nomor: '', jenis_kendaraan: 'mobil', warna: '', jenis_pelanggan_id: '' };
            this.modalOpen = true;
        },
        openEdit(item) {
            this.mode = 'edit';
            this.formAction = '/management/kendaraan/' + item.id;
            this.method = 'PUT';
            this.form = { pemilik: item.pemilik, plat_nomor: item.plat_nomor, jenis_kendaraan: item.jenis_kendaraan, warna: item.warna, jenis_pelanggan_id: item.jenis_pelanggan_id ?? '' };
            this.modalOpen = true;
        }
    }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-500">Total kendaraan: {{ $vehicles->count() }}</p>
                </div>
                <button type="button" @click="openCreate()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    Tambah Kendaraan
                </button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Pemilik</th>
                                <th class="px-4 py-3">Plat Nomor</th>
                                <th class="px-4 py-3">Jenis</th>
                                <th class="px-4 py-3">Warna</th>
                                <th class="px-4 py-3">Pelanggan</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($vehicles as $vehicle)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $vehicle->pemilik }}</td>
                                    <td class="px-4 py-3">{{ $vehicle->plat_nomor }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($vehicle->jenis_kendaraan) }}</td>
                                    <td class="px-4 py-3">{{ $vehicle->warna }}</td>
                                    <td class="px-4 py-3">{{ $vehicle->jenisPelanggan?->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="openEdit({ id: '{{ $vehicle->id }}', pemilik: '{{ addslashes($vehicle->pemilik) }}', plat_nomor: '{{ addslashes($vehicle->plat_nomor) }}', jenis_kendaraan: '{{ $vehicle->jenis_kendaraan }}', warna: '{{ addslashes($vehicle->warna) }}', jenis_pelanggan_id: '{{ $vehicle->jenis_pelanggan_id ?? '' }}' })" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-100">
                                                Edit
                                            </button>
                                            <form action="{{ route('management.vehicle.destroy', $vehicle) }}" method="POST" onsubmit="return confirm('Yakin hapus kendaraan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 font-medium text-rose-700 hover:bg-rose-100">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada data kendaraan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="modalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            {{--
                Modal ini dipakai untuk form tambah/edit kendaraan.
                Input utama:
                - pemilik: nama pemilik kendaraan.
                - plat_nomor: nomor polisi, biasanya unik untuk tiap kendaraan.
                - jenis_kendaraan: mobil/motor/truk.
                - warna: warna kendaraan.
                - jenis_pelanggan_id: status/jenis pelanggan yang terkait dengan kendaraan.
            --}}
            <div class="absolute inset-0 bg-slate-900/50" @click="modalOpen = false"></div>
            <div class="relative w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800" x-text="mode === 'create' ? 'Tambah Kendaraan' : 'Edit Kendaraan'"></h3>
                    <button type="button" @click="modalOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form :action="formAction" method="POST">
                    @csrf
                    <input type="hidden" name="_method" :value="method">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Pemilik</label>
                            <input x-model="form.pemilik" type="text" name="pemilik" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Plat Nomor</label>
                            <input x-model="form.plat_nomor" type="text" name="plat_nomor" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Jenis Kendaraan</label>
                            <select x-model="form.jenis_kendaraan" name="jenis_kendaraan" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                                <option value="mobil">Mobil</option>
                                <option value="motor">Motor</option>
                                <option value="truk">Truk</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Warna</label>
                            <input x-model="form.warna" type="text" name="warna" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Jenis Pelanggan</label>
                            <select x-model="form.jenis_pelanggan_id" name="jenis_pelanggan_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                                <option value="">-- Tidak dipilih --</option>
                                @foreach ($jenisPelanggan as $jenis)
                                    <option value="{{ $jenis->id }}">{{ $jenis->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="modalOpen = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
