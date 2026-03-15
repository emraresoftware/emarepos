<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Seç — Emare POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full bg-gray-50 font-sans antialiased text-gray-800">
    <div class="min-h-full flex items-center justify-center px-6 py-10">
        <div class="w-full max-w-4xl">
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center mx-auto shadow-lg shadow-brand-500/30">
                    <span class="text-white font-bold text-2xl">EP</span>
                </div>
                <h1 class="mt-4 text-3xl font-bold text-gray-900">Terminal Seçimi</h1>
                <p class="mt-2 text-sm text-gray-500">Bu oturumun hangi kasa veya hızlı satış ekranında çalışacağını seçin.</p>
            </div>

            @if($errors->any())
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('pos.terminal.select.store') }}" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($terminals as $terminal)
                        <label class="group cursor-pointer">
                            <input type="radio" name="terminal_id" value="{{ $terminal->id }}" class="sr-only peer" {{ old('terminal_id') == $terminal->id ? 'checked' : '' }}>
                            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition-all peer-checked:border-brand-500 peer-checked:ring-4 peer-checked:ring-brand-500/10 group-hover:border-brand-300">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $terminal->name }}</h2>
                                            @if($terminal->code)
                                                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-semibold text-brand-700">#{{ $terminal->code }}</span>
                                            @endif
                                        </div>
                                        @if($terminal->description)
                                            <p class="mt-2 text-sm text-gray-500">{{ $terminal->description }}</p>
                                        @else
                                            <p class="mt-2 text-sm text-gray-400">Bu terminal için açıklama girilmemiş.</p>
                                        @endif
                                    </div>
                                    <div class="w-10 h-10 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center peer-checked:bg-brand-500 peer-checked:text-white">
                                        <i class="fas fa-cash-register"></i>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-center pt-2">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-white font-semibold bg-gradient-to-r from-brand-500 to-purple-600 shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 transition-all">
                        <i class="fas fa-arrow-right"></i>
                        <span>Terminal ile Devam Et</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>