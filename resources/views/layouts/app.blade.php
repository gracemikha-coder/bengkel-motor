<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Bengkel Motor') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-800 antialiased">
        @php
            $menu = [
                ['label' => 'Beranda', 'route' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Katalog', 'route' => 'katalog', 'icon' => 'cube'],
                ['label' => 'Kasir', 'route' => 'kasir', 'icon' => 'cash'],
                ['label' => 'Barang masuk', 'route' => 'barang-masuk', 'icon' => 'truck'],
                ['label' => 'Laporan', 'route' => 'laporan', 'icon' => 'chart'],
            ];
        @endphp

        <div class="min-h-screen lg:flex" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
            <div x-cloak x-show="sidebarOpen" x-transition.opacity class="no-print fixed inset-0 z-30 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

            <aside id="main-sidebar" x-cloak class="no-print fixed inset-y-0 left-0 z-40 flex w-72 flex-col justify-between bg-slate-900 p-6 text-slate-100 transition-transform duration-200 ease-out lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
                <div>
                    <div class="mb-8 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <img src="{{ asset('images/logo-bengkel.png') }}" alt="Logo Bengkel Motor" class="h-10 w-10 shrink-0 rounded-lg object-contain">
                            <div class="min-w-0">
                                <p class="text-lg font-semibold tracking-tight">Bengkel Motor</p>
                            </div>
                        </div>
                        <button type="button" @click="sidebarOpen = false" aria-label="Tutup menu" class="rounded-lg p-2 text-slate-300 hover:bg-slate-800 hover:text-white lg:hidden">×</button>
                    </div>

                    <nav class="space-y-2">
                        @foreach($menu as $item)
                            @php $active = request()->routeIs($item['route']); @endphp
                            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false" class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition {{ $active ? 'bg-blue-700 text-white shadow-lg shadow-blue-900/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                @include('components.icon.' . $item['icon'])
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="pt-6">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-slate-600 hover:text-white">
                        @include('components.icon.logout')
                        Keluar
                    </button>
                </form>
            </aside>

            <div class="flex min-h-screen min-w-0 flex-1 flex-col lg:pl-72">
                <header class="no-print border-b border-slate-200 bg-white/90 backdrop-blur-sm">
                    <div class="flex items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="sidebarOpen = true" aria-controls="main-sidebar" :aria-expanded="sidebarOpen.toString()" aria-label="Buka menu" class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-800 text-white lg:hidden">
                                @include('components.icon.menu')
                            </button>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Bengkel</p>
                                <h1 class="text-xl font-bold text-slate-800">{{ $header ?? 'Dashboard' }}</h1>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="hidden items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 sm:flex">
                                @include('components.icon.user')
                                <span>{{ Auth::user()->name ?? 'Admin' }}</span>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    @include('components.icon.logout')
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="min-w-0 flex-1 px-4 py-3 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
