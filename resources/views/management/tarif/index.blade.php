<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            {{ __('Tarif & Jenis Pelanggan') }}
        </h2>
    </x-slot>

    <div class="py-8" x-data="{
        tab: 'tarif',
        tarifModalOpen: false,
        jenisModalOpen: false,
        tarifAction: '{{ route('management.tarif.store') }}',
        tarifMethod: 'POST',
        tarifForm: { jenis_kendaraan: 'mobil', tarif: '' },
        jenisAction: '{{ route('management.jenis-pelanggan.store') }}',
        jenisMethod: 'POST',
        jenisForm: { nama: '', deskripsi: '', is_gratis_parkir: false, is_bebas_denda: false, prioritas_level: 1, status: 'aktif', denda: false },
        openTarifCreate() {
            this.tarifAction = '{{ route('management.tarif.store') }}';
            this.tarifMethod = 'POST';
            this.tarifForm = { jenis_kendaraan: 'mobil', tarif: '' };
            this.tarifModalOpen = true;
        },
        openTarifEdit(item) {
            this.tarifAction = '/management/tarif/' + item.id;
            this.tarifMethod = 'PUT';
            this.tarifForm = { jenis_kendaraan: item.jenis_kendaraan, tarif: item.tarif };
            this.tarifModalOpen = true;
        },
        openJenisCreate() {
            this.jenisAction = '{{ route('management.jenis-pelanggan.store') }}';
            this.jenisMethod = 'POST';
            this.jenisForm = { nama: '', deskripsi: '', is_gratis_parkir: false, is_bebas_denda: false, prioritas_level: 1, status: 'aktif', denda: false };
            this.jenisModalOpen = true;
        },
        openJenisEdit(item) {
            this.jenisAction = '/management/jenis-pelanggan/' + item.id;
            this.jenisMethod = 'PUT';
            this.jenisForm = { nama: item.nama, deskripsi: item.deskripsi ?? '', is_gratis_parkir: item.is_gratis_parkir, is_bebas_denda: item.is_bebas_denda, prioritas_level: item.prioritas_level, status: item.status, denda: item.denda };
            this.jenisModalOpen = true;
        }
    }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                <button type="button" @click="tab = 'tarif'" :class="tab === 'tarif' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-sm font-medium transition">
                    Tarif
                </button>
                <button type="button" @click="tab = 'jenis'" :class="tab === 'jenis' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-sm font-medium transition">
                    Jenis Pelanggan
                </button>
            </div>

            <div x-show="tab === 'tarif'" x-transition class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Daftar tarif parkir</p>
                    </div>
                    <button type="button" @click="openTarifCreate()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Tambah Tarif
                    </button>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700">
                            <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Jenis Kendaraan</th>
                                    <th class="px-4 py-3">Tarif</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @forelse ($tarifs as $tarif)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ ucfirst($tarif->jenis_kendaraan) }}</td>
                                        <td class="px-4 py-3">Rp {{ number_format($tarif->tarif, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="openTarifEdit({ id: '{{ $tarif->id }}', jenis_kendaraan: '{{ $tarif->jenis_kendaraan }}', tarif: '{{ $tarif->tarif }}' })" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-100">
                                                    Edit
                                                </button>
                                                <form action="{{ route('management.tarif.destroy', $tarif) }}" method="POST" onsubmit="return confirm('Yakin hapus tarif ini?')">
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
                                        <td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada data tarif.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'jenis'" x-transition class="space-y-6" style="display: none;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Daftar jenis pelanggan</p>
                    </div>
                    <button type="button" @click="openJenisCreate()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Tambah Jenis
                    </button>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700">
                            <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Prioritas</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @forelse ($jenisPelanggan as $jenis)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $jenis->nama }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                {{ ucfirst($jenis->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">{{ $jenis->prioritas_level }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="openJenisEdit({ id: '{{ $jenis->id }}', nama: '{{ addslashes($jenis->nama) }}', deskripsi: '{{ addslashes($jenis->deskripsi ?? '') }}', is_gratis_parkir: {{ $jenis->is_gratis_parkir ? 'true' : 'false' }}, is_bebas_denda: {{ $jenis->is_bebas_denda ? 'true' : 'false' }}, prioritas_level: {{ $jenis->prioritas_level }}, status: '{{ $jenis->status }}', denda: {{ $jenis->denda ? 'true' : 'false' }} })" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-100">
                                                    Edit
                                                </button>
                                                <form action="{{ route('management.jenis-pelanggan.destroy', $jenis) }}" method="POST" onsubmit="return confirm('Yakin hapus jenis pelanggan ini?')">
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
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada jenis pelanggan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="tarifModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="absolute inset-0 bg-slate-900/50" @click="tarifModalOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Tarif</h3>
                    <button type="button" @click="tarifModalOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form :action="tarifAction" method="POST">
                    @csrf
                    <input type="hidden" name="_method" :value="tarifMethod">
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Jenis Kendaraan</label>
                            <select x-model="tarifForm.jenis_kendaraan" name="jenis_kendaraan" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                                <option value="mobil">Mobil</option>
                                <option value="motor">Motor</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tarif (Rp)</label>
                            <input x-model="tarifForm.tarif" type="number" min="0" name="tarif" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="tarifModalOpen = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="jenisModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="absolute inset-0 bg-slate-900/50" @click="jenisModalOpen = false"></div>
            <div class="relative w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Jenis Pelanggan</h3>
                    <button type="button" @click="jenisModalOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form :action="jenisAction" method="POST">
                    @csrf
                    <input type="hidden" name="_method" :value="jenisMethod">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                            <input x-model="jenisForm.nama" type="text" name="nama" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                            <textarea x-model="jenisForm.deskripsi" name="deskripsi" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"></textarea>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Prioritas</label>
                            <input x-model="jenisForm.prioritas_level" type="number" min="1" max="5" name="prioritas_level" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                            <select x-model="jenisForm.status" name="status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <span class="text-sm font-medium text-slate-700">Gratis Parkir</span>
                            <input x-model="jenisForm.is_gratis_parkir" type="checkbox" name="is_gratis_parkir" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <span class="text-sm font-medium text-slate-700">Bebas Denda</span>
                            <input x-model="jenisForm.is_bebas_denda" type="checkbox" name="is_bebas_denda" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 md:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Denda</span>
                            <input x-model="jenisForm.denda" type="checkbox" name="denda" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="jenisModalOpen = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
