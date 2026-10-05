<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - RegRun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 shadow-2xl border border-slate-800">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-orange-500 to-amber-500 flex items-center justify-center text-white text-2xl font-black mx-auto mb-3 shadow-lg">
                R
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">RegRun Admin</h1>
            <p class="text-xs text-slate-500 mt-1">Sistem Manajemen Transaksi &amp; Pendaftaran Event Lari</p>
        </div>

        @if($errors->any())
            <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Administrator</label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email', 'admin@regrun.test') }}" 
                       required 
                       placeholder="admin@email.com" 
                       class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                <input type="password" 
                       name="password" 
                       value="password" 
                       required 
                       placeholder="••••••••" 
                       class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" />
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                    <span>Ingat Saya</span>
                </label>
                <span class="text-slate-400">Default: password</span>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold text-sm shadow-lg shadow-orange-600/30 transition mt-2">
                Masuk ke Panel Admin &rarr;
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <a href="{{ route('register.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium">
                &larr; Kembali ke Halaman Pendaftaran
            </a>
        </div>
    </div>
</body>
</html>
