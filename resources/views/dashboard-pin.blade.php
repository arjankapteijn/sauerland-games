<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sauerland Games — Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-6">
    <form method="POST" action="{{ route('dashboard.pin.store') }}" class="w-full max-w-xs">
        @csrf
        <p class="text-sm uppercase tracking-widest text-emerald-500 font-semibold mb-2">Sauerland Games</p>
        <h1 class="text-2xl font-bold mb-6">Pincode</h1>

        <input type="password" name="pin" inputmode="numeric" autofocus
               class="w-full rounded-lg bg-slate-900 border border-slate-800 px-4 py-3 text-lg tracking-widest text-center focus:outline-none focus:ring-2 focus:ring-emerald-600">

        @error('pin')
            <p class="text-sm text-red-400 mt-2">{{ $message }}</p>
        @enderror

        <button type="submit" class="mt-4 w-full rounded-lg bg-emerald-600 hover:bg-emerald-500 py-3 font-semibold">
            Openen
        </button>
    </form>
</body>
</html>
