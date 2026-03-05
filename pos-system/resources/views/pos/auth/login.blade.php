<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap — Emare POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81',950:'#1e1b4b' }
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #4f46e5, #7c3aed, #6d28d9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        @keyframes gradient { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
        .animate-gradient { animation: gradient 8s ease infinite; background-size: 400% 400%; }
    </style>
</head>
<body class="h-full font-sans antialiased">
    <div class="h-full flex">
        <!-- Left: Decorative Panel -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 animate-gradient relative items-center justify-center overflow-hidden">
            <!-- Pattern Overlay -->
            <div class="absolute inset-0" style="background-image: radial-gradient(at 80% 20%, rgba(99,102,241,0.15) 0px, transparent 50%), radial-gradient(at 20% 80%, rgba(139,92,246,0.1) 0px, transparent 50%);"></div>
            <!-- Floating Blobs -->
            <div class="absolute top-20 left-20 w-72 h-72 bg-brand-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
            <!-- Content -->
            <div class="relative z-10 text-center px-12">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center mx-auto mb-6 shadow-xl shadow-brand-500/30">
                    <span class="text-white font-bold text-3xl">EF</span>
                </div>
                <h1 class="text-4xl font-extrabold text-white mb-3">Emare Finance</h1>
                <p class="text-brand-200 text-lg">POS ve muhasebe yazılımınız</p>
                <div class="mt-10 grid grid-cols-3 gap-6 max-w-sm mx-auto">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white">500+</div>
                        <div class="text-brand-300 text-xs mt-1">Aktif İşletme</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white">50K+</div>
                        <div class="text-brand-300 text-xs mt-1">Günlük İşlem</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white">%99.9</div>
                        <div class="text-brand-300 text-xs mt-1">Uptime</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Login Form -->
        <div class="flex-1 flex items-center justify-center bg-gray-50 px-6">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden text-center mb-8">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-brand-500/30">
                        <span class="text-white font-bold text-xl">EF</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">Emare <span class="gradient-text">Finance</span></h1>
                </div>

                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Hoş Geldiniz</h2>
                        <p class="text-gray-500 text-sm mt-1">Hesabınıza giriş yapın</p>
                    </div>

                    <form method="POST" action="{{ route('pos.login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-envelope text-brand-400 mr-1"></i> E-posta Adresi
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all duration-300"
                                   placeholder="ornek@email.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-lock text-brand-400 mr-1"></i> Şifre
                            </label>
                            <input type="password" name="password" required
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition-all duration-300"
                                   placeholder="••••••••">
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                <span class="text-sm text-gray-600">Beni hatırla</span>
                            </label>
                        </div>

                        @if($errors->any())
                            <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-red-600 text-sm">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <button type="submit" class="w-full py-3.5 rounded-xl text-white font-semibold bg-gradient-to-r from-brand-500 to-purple-600 shadow-lg shadow-brand-500/30 hover:shadow-brand-500/50 hover:scale-[1.02] transition-all duration-300">
                            <i class="fas fa-sign-in-alt mr-2"></i> Giriş Yap
                        </button>
                    </form>
                </div>

                <div class="mt-6 text-center text-xs text-gray-400">
                    Emare Finance POS Sistemi v1.0
                </div>
            </div>
        </div>
    </div>
</body>
</html>
