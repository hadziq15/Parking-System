<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            {{ __('Kendaraan Masuk') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
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

            @php
                $hasRegularType = $jenisPelanggan->contains(fn ($jenis) => strtolower($jenis->nama) === 'reguler');
            @endphp

            @if (! $hasRegularType)
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Jenis pelanggan <strong>Reguler</strong> belum ada. Tambahkan data jenis pelanggan <strong>Reguler</strong> agar transaksi parkir bisa dilakukan.
                    <a href="{{ route('management.tarif.index', ['active_tab' => 'jenis']) }}" class="ml-1 font-semibold underline">
                        Tambah sekarang
                    </a>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('parkir.masuk.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="plat_nomor" class="mb-1 block text-sm font-medium text-slate-700">Nomor Polisi</label>
                            <input id="plat_nomor" name="plat_nomor" type="text" value="{{ old('plat_nomor') }}" required
                                placeholder="Contoh: B 1234 ABC"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            <p id="vehicle-info" class="mt-2 hidden text-xs text-slate-600"></p>
                        </div>

                        <div>
                            <label for="jenis_kendaraan" class="mb-1 block text-sm font-medium text-slate-700">Jenis Kendaraan</label>
                            <select id="jenis_kendaraan" name="jenis_kendaraan" required
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                                <option value="">Pilih jenis kendaraan</option>
                                <option value="mobil" {{ old('jenis_kendaraan') === 'mobil' ? 'selected' : '' }}>Mobil</option>
                                <option value="motor" {{ old('jenis_kendaraan') === 'motor' ? 'selected' : '' }}>Motor</option>
                                <option value="truk" {{ old('jenis_kendaraan') === 'truk' ? 'selected' : '' }}>Truk</option>
                            </select>
                        </div>

                        <div>
                            <label for="area_parkir_id" class="mb-1 block text-sm font-medium text-slate-700">Area Parkir</label>
                            <select id="area_parkir_id" name="area_parkir_id" required
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                                <option value="">Pilih area parkir</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}" {{ old('area_parkir_id') == $area->id ? 'selected' : '' }}>
                                        {{ $area->nama }} - {{ $area->lokasi }}
                                        @if ($area->tarif)
                                            - {{ ucfirst($area->tarif->jenis_kendaraan) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                            Batal
                        </a>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                            {{ $hasRegularType ? '' : 'disabled' }}>
                            Simpan Kendaraan Masuk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const registeredVehicles = @json($registeredVehicles);
        const platInput = document.getElementById('plat_nomor');
        const vehicleInfo = document.getElementById('vehicle-info');

        function updateVehicleInfo() {
            const rawValue = (platInput.value || '').trim();
            const formatted = rawValue.toUpperCase();

            if (!formatted) {
                vehicleInfo.classList.add('hidden');
                vehicleInfo.textContent = '';
                return;
            }

            const match = registeredVehicles.find((vehicle) => vehicle.plat_nomor === formatted);
            const jenisPelanggan = match ? match.jenis_pelanggan : 'Reguler';

            vehicleInfo.textContent = 'Jenis pelanggan: ' + jenisPelanggan;
            vehicleInfo.classList.remove('hidden');
        }

        platInput.addEventListener('input', updateVehicleInfo);
        updateVehicleInfo();
    </script>
</x-app-layout>
