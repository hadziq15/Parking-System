{{--
    Catatan pembelajaran:
    View Blade ini menampilkan tampilan halaman aplikasi. Komponen utama seperti form, tabel, dan modal dipasang di sini, lalu diberi data dari controller melalui compact() atau session().
    Struktur dasar view: menerima data, menampilkan HTML, lalu menyisipkan interaksi JavaScript jika diperlukan.
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div x-data="{
        notifications: [],
        addNotification(type, message) {
            const id = Date.now() + Math.random();
            this.notifications.push({ id, type, message });

            setTimeout(() => this.removeNotification(id), 4000);
        },
        removeNotification(id) {
            this.notifications = this.notifications.filter((notification) => notification.id !== id);
        }
    }" x-init="
        @if (session('success'))
            this.addNotification('success', @js(session('success')));
        @endif
        @if (session('error'))
            this.addNotification('error', @js(session('error')));
        @endif
        @if (session('status'))
            this.addNotification('info', @js(session('status')));
        @endif
        @if ($errors->any())
            this.addNotification('error', @js($errors->first()));
        @endif
    " class="min-h-screen bg-slate-100">
        <div class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-3">
            <template x-for="notification in notifications" :key="notification.id">
                <div x-show="true" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-x-6 opacity-0"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-auto rounded-xl border border-slate-200 bg-white p-4 shadow-lg ring-1 ring-slate-200"
                    :class="{
                        'border-emerald-200 bg-emerald-50 text-emerald-900': notification.type === 'success',
                        'border-rose-200 bg-rose-50 text-rose-900': notification.type === 'error',
                        'border-sky-200 bg-sky-50 text-sky-900': notification.type === 'info'
                    }">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full"
                            :class="{
                                'bg-emerald-100 text-emerald-600': notification.type === 'success',
                                'bg-rose-100 text-rose-600': notification.type === 'error',
                                'bg-sky-100 text-sky-600': notification.type === 'info'
                            }">
                            <span x-text="notification.type === 'success' ? '✓' : notification.type === 'error' ? '!' : 'i'" class="text-sm font-bold"></span>
                        </div>

                        <div class="flex-1">
                            <p class="text-sm font-semibold" x-text="notification.type === 'success' ? 'Berhasil' : notification.type === 'error' ? 'Perhatian' : 'Informasi'"></p>
                            <p class="mt-1 text-sm" x-text="notification.message"></p>
                        </div>

                        <button type="button" @click="removeNotification(notification.id)"
                            class="ml-2 text-slate-500 transition hover:text-slate-700" aria-label="Tutup notifikasi">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        @include('layouts.navigation')

        <div class="lg:pl-64">
            @isset($header)
                <header class="bg-white shadow-sm ring-1 ring-slate-200/80">
                    <div class="mx-auto flex items-center gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <button @click="sidebarOpen = !sidebarOpen" type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:hidden"
                            aria-label="Buka atau tutup menu sidebar">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            {{ $header }}
                        </div>
                    </div>
                </header>
            @endisset

            <main class="min-w-0">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
