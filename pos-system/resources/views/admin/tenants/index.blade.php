@extends('admin.layout')

@section('title', 'Tenantlar')
@section('page-title', 'Tenantlar')
@section('page-sub', 'Tüm kayıtlı işletmeler')

@section('content')

<div x-data="{ showCreate: {{ $errors->any() ? 'true' : 'false' }}, slugGenerated: false }"
     @keydown.escape.window="showCreate = false">

{{-- ─── Tenant Oluştur Modalı ─── --}}
<div x-show="showCreate" x-cloak
     class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 backdrop-blur-sm overflow-y-auto pt-10 pb-10">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-2xl mx-4" @click.stop>
        <div class="flex items-center justify-between p-6 border-b border-slate-700">
            <div>
                <h3 class="text-base font-semibold text-white">Yeni İşletme Ekle</h3>
                <p class="text-xs text-slate-500 mt-0.5">Şube, vergi oranları ve admin kullanıcı otomatik oluşturulur.</p>
            </div>
            <button @click="showCreate = false" class="text-slate-500 hover:text-red-400 text-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.tenants.store') }}" class="p-6 space-y-5">
            @csrf

            {{-- Hata mesajları --}}
            @if($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4">
                    <ul class="text-xs text-red-400 space-y-1">
                        @foreach($errors->all() as $err)
                            <li><i class="fas fa-exclamation-circle mr-1"></i>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- İşletme Bilgileri --}}
            <div class="space-y-1">
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">İşletme Bilgileri</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">İşletme Adı *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="Örn: Lezzet Cafe"
                               @input="
                                   if (!slugGenerated) {
                                       $el.closest('form').querySelector('[name=slug]').value =
                                           $event.target.value.toLowerCase()
                                           .replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s')
                                           .replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c')
                                           .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
                                   }
                               "
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500 placeholder-slate-600">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Slug * (URL kimliği)</label>
                        <input type="text" name="slug" value="{{ old('slug') }}" required
                               placeholder="lezzet-cafe"
                               @input="slugGenerated = true"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500 placeholder-slate-600 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Fatura E-posta *</label>
                        <input type="email" name="billing_email" value="{{ old('billing_email') }}" required
                               placeholder="info@lezzetcafe.com"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500 placeholder-slate-600">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Plan *</label>
                        <select name="plan_id" required
                                class="w-full bg-slate-800 border border-slate-700 text-slate-300 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500">
                            <option value="">Plan seç…</option>
                            @foreach($planlar as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Durum *</label>
                        <select name="status" required
                                class="w-full bg-slate-800 border border-slate-700 text-slate-300 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500">
                            <option value="active"  {{ old('status','active') === 'active'  ? 'selected' : '' }}>Aktif</option>
                            <option value="trial"   {{ old('status') === 'trial'   ? 'selected' : '' }}>Deneme (14 gün)</option>
                            <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Askıda</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Şube Bilgileri --}}
            <div class="space-y-1 border-t border-slate-800 pt-4">
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Şube (İlk Şube)</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Şube Adı</label>
                        <input type="text" name="branch_name" value="{{ old('branch_name', 'Merkez Şube') }}"
                               placeholder="Merkez Şube"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500 placeholder-slate-600">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Şehir</label>
                        <input type="text" name="branch_city" value="{{ old('branch_city', 'İstanbul') }}"
                               placeholder="İstanbul"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500 placeholder-slate-600">
                    </div>
                </div>
            </div>

            {{-- Admin Kullanıcı --}}
            <div class="space-y-1 border-t border-slate-800 pt-4">
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Admin Kullanıcı</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Ad Soyad *</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                               placeholder="Ahmet Yılmaz"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500 placeholder-slate-600">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">E-posta *</label>
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                               placeholder="admin@isleme.com"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500 placeholder-slate-600">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-slate-400 mb-1">Şifre * (min. 6 karakter)</label>
                        <input type="password" name="admin_password" required minlength="6"
                               placeholder="••••••••"
                               class="w-full bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-brand-500 placeholder-slate-600">
                    </div>
                </div>
            </div>

            {{-- Butonlar --}}
            <div class="flex gap-3 border-t border-slate-800 pt-5">
                <button type="button" @click="showCreate = false"
                        class="flex-1 px-4 py-2.5 text-sm text-slate-400 bg-slate-800 hover:bg-slate-700 rounded-xl transition-colors">
                    İptal
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 text-sm text-white font-medium bg-gradient-to-r from-brand-600 to-purple-700 hover:opacity-90 rounded-xl transition-opacity shadow-lg shadow-brand-900/30">
                    <i class="fas fa-plus mr-1.5"></i> İşletmeyi Oluştur
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Filtreler + Tenant Ekle Butonu --}}
<form method="GET" class="flex flex-wrap items-center gap-3 mb-6">
    <div class="relative">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="İşletme / slug / e-posta ara..."
               class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg pl-9 pr-4 py-2 w-64 focus:outline-none focus:border-brand-500 placeholder-slate-500">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
    </div>
    <select name="status"
            class="bg-slate-800 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-brand-500">
        <option value="">Tüm Durumlar</option>
        <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Aktif</option>
        <option value="trial"     {{ request('status') === 'trial'     ? 'selected' : '' }}>Deneme</option>
        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Askıda</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>İptal</option>
    </select>
    <button type="submit"
            class="bg-brand-600 hover:bg-brand-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-filter mr-1"></i> Filtrele
    </button>
    @if(request()->anyFilled(['search','status']))
        <a href="{{ route('admin.tenants') }}"
           class="text-slate-400 hover:text-white text-sm px-3 py-2 rounded-lg hover:bg-slate-700 transition-colors">
            <i class="fas fa-times mr-1"></i> Temizle
        </a>
    @endif
    <span class="text-xs text-slate-500">{{ $tenants->total() }} kayıt</span>
    <button type="button" @click="showCreate = true"
            class="ml-auto flex items-center gap-2 bg-gradient-to-r from-brand-600 to-purple-700 hover:opacity-90 text-white text-sm px-4 py-2 rounded-lg transition-opacity font-medium shadow-lg shadow-brand-900/30">
        <i class="fas fa-plus"></i> Yeni İşletme
    </button>
</form>

{{-- Tablo --}}
<div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="text-xs text-slate-400 uppercase bg-slate-900/50 border-b border-slate-700">
            <tr>
                <th class="px-4 py-3">İşletme</th>
                <th class="px-4 py-3">Plan</th>
                <th class="px-4 py-3">Kullanıcı</th>
                <th class="px-4 py-3">Durum</th>
                <th class="px-4 py-3">Süre Sonu</th>
                <th class="px-4 py-3">Kayıt</th>
                <th class="px-4 py-3 text-center">İşlem</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
            @forelse($tenants as $tenant)
                <tr class="hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div>
                            <p class="font-medium text-slate-100">{{ $tenant->name }}</p>
                            <p class="text-xs text-slate-500">{{ $tenant->slug }}</p>
                            @if($tenant->billing_email)
                                <p class="text-[10px] text-slate-600">{{ $tenant->billing_email }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-300">{{ $tenant->plan?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-300">{{ $tenant->users_count }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium
                            {{ $tenant->status === 'active'    ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' :
                               ($tenant->status === 'trial'    ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' :
                               ($tenant->status === 'suspended'? 'bg-red-500/20 text-red-400 border border-red-500/30' :
                                                                 'bg-slate-700 text-slate-400')) }}">
                            {{ match($tenant->status) {
                                'active'    => 'Aktif',
                                'trial'     => 'Deneme',
                                'suspended' => 'Askıda',
                                'cancelled' => 'İptal',
                                default     => $tenant->status,
                            } }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-xs">
                        {{ $tenant->trial_ends_at?->format('d.m.Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        {{ $tenant->created_at->format('d.m.Y') }}
                    </td>
                    <td class="px-4 py-3 text-center" x-data="{ open: false }">
                        <div class="relative inline-block">
                            <button @click="open = !open" @click.away="open = false"
                                    class="text-slate-400 hover:text-white text-xs border border-slate-700 hover:border-slate-500 px-2.5 py-1.5 rounded-lg transition-colors">
                                <i class="fas fa-ellipsis"></i>
                            </button>
                            <div x-show="open" x-cloak
                                 class="absolute right-0 mt-1 w-44 bg-slate-900 border border-slate-700 rounded-xl shadow-xl z-20 overflow-hidden">
                                @foreach([
                                    ['status' => 'active',    'label' => 'Aktif Yap',    'icon' => 'fa-check-circle',   'cls' => 'text-emerald-400'],
                                    ['status' => 'trial',     'label' => 'Denemeye Al',  'icon' => 'fa-hourglass-half', 'cls' => 'text-amber-400'],
                                    ['status' => 'suspended', 'label' => 'Askıya Al',    'icon' => 'fa-pause-circle',   'cls' => 'text-red-400'],
                                ] as $opt)
                                    @if($tenant->status !== $opt['status'])
                                        <form method="POST"
                                              action="{{ route('admin.tenants.status', $tenant) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $opt['status'] }}">
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2.5 text-xs {{ $opt['cls'] }} hover:bg-slate-800 flex items-center gap-2 transition-colors">
                                                <i class="fas {{ $opt['icon'] }} w-4"></i>
                                                {{ $opt['label'] }}
                                            </button>
                                        </form>
                                    @endif
                                @endforeach
                                <div class="border-t border-slate-800"></div>
                                <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}"
                                      onsubmit="return confirm('{{ $tenant->name }} silinsin mi? Bu işlem geri alınamaz.')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2.5 text-xs text-red-500 hover:bg-slate-800 flex items-center gap-2 transition-colors">
                                        <i class="fas fa-trash w-4"></i> Sil
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                        <i class="fas fa-building-circle-xmark text-2xl mb-2 block"></i>
                        Kayıt bulunamadı.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Sayfalama --}}
@if($tenants->hasPages())
    <div class="mt-5 flex justify-center">
        {{ $tenants->links() }}
    </div>
@endif

</div>{{-- /x-data --}}
@endsection
