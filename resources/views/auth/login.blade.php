<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-9">
            <div class="mb-8 flex items-center gap-3">
                <img src="{{ asset('images/logo-bengkel.png') }}" alt="Logo Bengkel Motor" class="h-12 w-12 shrink-0 object-contain">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Bengkel motor</p>
                    <h1 class="text-xl font-bold text-slate-900">Bengkel Motor</h1>
                </div>
            </div>

            <div>
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <div class="mb-8">
                        <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-400">Akses admin</p>
                        <h2 class="mt-2 text-3xl font-bold text-slate-800">Masuk ke akun</h2>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-slate-800" placeholder="nama@bengkel.com">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                            <div class="relative">
                                <input id="password" type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 pr-28 text-slate-800" placeholder="Masukkan password">
                                <button id="toggle-password" type="button" aria-controls="password" aria-pressed="false" class="absolute inset-y-0 right-3 flex items-center text-sm font-medium text-slate-500">Lihat password</button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-blue-700">
                                Ingat saya
                            </label>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-blue-800 px-4 py-3 text-base font-semibold text-white transition hover:bg-blue-700">Masuk</button>
                    </form>
                    <script>
                        document.getElementById('toggle-password')?.addEventListener('click', function () {
                            const password = document.getElementById('password');
                            const visible = password.type === 'password';
                            password.type = visible ? 'text' : 'password';
                            this.textContent = visible ? 'Sembunyikan' : 'Lihat password';
                            this.setAttribute('aria-pressed', String(visible));
                        });
                    </script>
            </div>
        </div>
    </div>
</x-guest-layout>

