@extends('pos.layouts.app')
@section('title', 'Personel Yönetimi')

@section('content')
<div class="flex-1 overflow-y-auto p-5 space-y-5" x-data="staffManager()" x-init="init()" x-cloak>

    {{-- ── Başlık ── --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Personel</h1>
            <p class="text-sm text-gray-500 mt-0.5">Çalışan listesi ve performans takibi</p>
        </div>
        <button @click="openModal()"
                class="px-4 py-2 rounded-xl text-sm font-semibold text-white
                       bg-gradient-to-r from-brand-500 to-purple-600
                       shadow-lg shadow-brand-500/20 hover:shadow-brand-500/40
                       hover:scale-105 transition-all duration-200">
            <i class="fas fa-user-plus mr-1.5"></i> Personel Ekle
        </button>
    </div>

    {{-- ── İstatistikler ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Toplam Personel</span>
                <div class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center">
                    <i class="fas fa-users text-brand-500 text-sm"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Aktif</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-circle-check text-emerald-500 text-sm"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-emerald-600">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Toplam Satış</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center">
                    <i class="fas fa-coins text-purple-500 text-sm"></i>
                </div>
            </div>
            <div class="text-lg font-bold text-purple-600">{{ number_format($stats['total_sales'], 2, ',', '.') }} ₺</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">En Çok Satan</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i class="fas fa-trophy text-amber-500 text-sm"></i>
                </div>
            </div>
            <div class="text-sm font-bold text-amber-600 truncate">{{ $stats['top_seller'] }}</div>
        </div>
    </div>

    {{-- ── Arama ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-lg shadow-gray-100/50 p-4">
        <form method="GET" action="{{ route('pos.staff') }}" class="flex items-center gap-2">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="İsim, telefon veya görev ara..."
                       class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
            </div>
            <select name="active" class="px-3 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 outline-none text-sm text-gray-600">
                <option value="">Tümü</option>
                <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Pasif</option>
            </select>
            <button type="submit"
                    class="px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-medium transition-colors">
                <i class="fas fa-filter"></i>
            </button>
        </form>
    </div>

    {{-- ── Tablo ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-lg shadow-gray-100/50 overflow-hidden">
        @if($staff->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Personel</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Görev</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">İletişim</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Satış</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">İşlem</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Yetki</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Durum</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($staff as $member)
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500/10 to-purple-500/10
                                            flex items-center justify-center text-brand-600 font-bold text-sm">
                                    {{ mb_substr($member->name, 0, 1) }}
                                </div>
                                <span class="font-semibold text-gray-800">{{ $member->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500">
                            @if($member->role)
                                <span class="px-2.5 py-1 bg-brand-50 text-brand-600 rounded-full text-xs font-medium">{{ $member->role }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs space-y-0.5">
                            @if($member->phone)
                                <div><i class="fas fa-phone w-3 mr-1"></i>{{ $member->phone }}</div>
                            @endif
                            @if($member->email)
                                <div><i class="fas fa-envelope w-3 mr-1"></i>{{ $member->email }}</div>
                            @endif
                            @if(!$member->phone && !$member->email)
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right font-bold text-gray-800">
                            {{ number_format($member->total_sales, 2, ',', '.') }} ₺
                        </td>
                        <td class="px-5 py-3.5 text-right text-gray-500">
                            {{ number_format($member->total_transactions) }}
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @php $permCount = is_array($member->permissions) ? count($member->permissions) : (($member->permissions && $member->permissions !== 'null') ? count(json_decode($member->permissions, true) ?? []) : 0); @endphp
                            @if($permCount > 0)
                                <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">{{ $permCount }} yetki</span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($member->is_active)
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">Aktif</span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-medium">Pasif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="showPerformance({{ $member->id }})"
                                        class="p-1.5 text-gray-400 hover:text-emerald-500 hover:bg-emerald-50 rounded-lg transition-colors" title="Performans">
                                    <i class="fas fa-chart-line text-xs"></i>
                                </button>
                                <button @click="editMember({{ json_encode($member) }})"
                                        class="p-1.5 text-gray-400 hover:text-brand-500 hover:bg-brand-50 rounded-lg transition-colors">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button @click="deleteMember({{ $member->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100">{{ $staff->withQueryString()->links() }}</div>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <i class="fas fa-id-badge text-5xl mb-4 text-gray-200"></i>
            <p class="font-medium text-gray-500">Henüz personel eklenmemiş</p>
            <p class="text-sm mt-1">Çalışanlarınızı ekleyerek satış takibini başlatın</p>
            <button @click="openModal()"
                    class="mt-4 px-5 py-2.5 bg-gradient-to-r from-brand-500 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-brand-500/20 hover:scale-105 transition-all">
                <i class="fas fa-user-plus mr-1.5"></i> Personel Ekle
            </button>
        </div>
        @endif
    </div>

    {{-- ── MODAL ── --}}
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="showModal = false"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500/10 to-purple-500/10
                                flex items-center justify-center">
                        <i class="fas fa-id-badge text-brand-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900" x-text="editId ? 'Personel Düzenle' : 'Yeni Personel'"></h3>
                </div>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
            </div>

            <div class="overflow-y-auto flex-1 p-6 space-y-5">
                {{-- Temel Bilgiler --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Ad Soyad <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.name" placeholder="örn. Ahmet Yılmaz"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Görev / Pozisyon</label>
                            <input type="text" x-model="form.role" placeholder="Garson, Kasiyer, Şef..."
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">PIN Kodu</label>
                            <input type="text" x-model="form.pin" placeholder="örn. 1234" maxlength="10"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefon</label>
                            <input type="tel" x-model="form.phone" placeholder="05XX XXX XX XX"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta</label>
                            <input type="email" x-model="form.email" placeholder="ornek@email.com"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                        </div>
                    </div>
                    <div x-show="editId">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Durum</label>
                        <div class="flex gap-2">
                            <button @click="form.is_active = true"
                                    :class="form.is_active ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500'"
                                    class="flex-1 py-2 border-2 rounded-xl text-sm font-medium transition-all">
                                <i class="fas fa-circle-check mr-1"></i> Aktif
                            </button>
                            <button @click="form.is_active = false"
                                    :class="!form.is_active ? 'border-gray-500 bg-gray-100 text-gray-700' : 'border-gray-200 text-gray-400'"
                                    class="flex-1 py-2 border-2 rounded-xl text-sm font-medium transition-all">
                                <i class="fas fa-circle-xmark mr-1"></i> Pasif
                            </button>
                        </div>
                    </div>
                </div>

                {{-- İzinler --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <h4 class="text-sm font-semibold text-gray-700"><i class="fas fa-shield-halved text-brand-500 mr-1"></i>Yetki & Kısıtlamalar</h4>
                        <button @click="selectAllPerms(true)" class="text-xs text-brand-500 hover:underline">Tümünü Seç</button>
                        <button @click="selectAllPerms(false)" class="text-xs text-red-400 hover:underline">Temizle</button>
                    </div>
                    @php
                    $permGroups = [
                        ['group' => 'Ürün Yönetimi', 'icon' => 'fa-box', 'color' => 'purple', 'perms' => [
                            ['key' => 'products.create',  'label' => 'Ürün ekleyebilsin'],
                            ['key' => 'products.edit',    'label' => 'Ürün düzenleyebilsin'],
                            ['key' => 'products.delete',  'label' => 'Ürün silebilsin'],
                        ]],
                        ['group' => 'Stok', 'icon' => 'fa-warehouse', 'color' => 'amber', 'perms' => [
                            ['key' => 'stock.edit',        'label' => 'Stok hareketi girebilsin'],
                        ]],
                        ['group' => 'Satışlar', 'icon' => 'fa-receipt', 'color' => 'emerald', 'perms' => [
                            ['key' => 'sales.view_all',    'label' => 'Tüm satışları görebilsin'],
                            ['key' => 'sales.view_own',    'label' => 'Sadece kendi satışlarını görebilsin'],
                            ['key' => 'sales.refund',      'label' => 'İade/iptal yapabilsin'],
                        ]],
                        ['group' => 'Müşteri & Cari', 'icon' => 'fa-users', 'color' => 'blue', 'perms' => [
                            ['key' => 'customers.create',  'label' => 'Müşteri ekleyebilsin'],
                            ['key' => 'customers.edit',    'label' => 'Müşteri düzenleyebilsin'],
                            ['key' => 'accounts.tahsilat', 'label' => 'Cari tahsilat ekleyebilsin'],
                            ['key' => 'accounts.delete',   'label' => 'Cari hareketi silebilsin'],
                        ]],
                        ['group' => 'Kasa & Raporlar', 'icon' => 'fa-cash-register', 'color' => 'gray', 'perms' => [
                            ['key' => 'register.open',     'label' => 'Kasa açabilsin/kapatabilsin'],
                            ['key' => 'reports.view',      'label' => 'Raporları görebilsin'],
                        ]],
                    ];
                    @endphp

                    <div class="space-y-3">
                        @foreach($permGroups as $group)
                        <div class="border border-gray-100 rounded-xl overflow-hidden">
                            <div class="bg-gray-50 px-3 py-2 flex items-center gap-2">
                                <i class="fas {{ $group['icon'] }} text-{{ $group['color'] }}-500 text-xs"></i>
                                <span class="text-xs font-semibold text-gray-600">{{ $group['group'] }}</span>
                            </div>
                            <div class="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($group['perms'] as $perm)
                                <label class="flex items-center gap-2 cursor-pointer group/perm">
                                    <input type="checkbox"
                                           :checked="form.permissions.includes('{{ $perm['key'] }}')"
                                           @change="togglePerm('{{ $perm['key'] }}')"
                                           class="w-4 h-4 rounded accent-brand-500">
                                    <span class="text-sm text-gray-700 group-hover/perm:text-brand-600">{{ $perm['label'] }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex gap-3 p-6 border-t border-gray-100 shrink-0">
                <button @click="showModal = false"
                        class="flex-1 px-4 py-2.5 rounded-xl border-2 border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                    İptal
                </button>
                <button @click="submitForm()" :disabled="saving"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white
                               bg-gradient-to-r from-brand-500 to-purple-600
                               shadow-lg shadow-brand-500/20 hover:scale-[1.02] transition-all">
                    <span x-show="!saving">Kaydet</span>
                    <span x-show="saving"><i class="fas fa-spinner fa-spin mr-1"></i> Kaydediliyor...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Performans Modal --}}
    <div x-show="perfModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="perfModal = false"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between shrink-0">
                <div>
                    <h2 class="text-lg font-bold text-gray-900"><i class="fas fa-chart-line text-emerald-500 mr-2"></i>Performans Raporu</h2>
                    <p class="text-sm text-gray-500 mt-0.5" x-text="perfData?.staff?.name || ''"></p>
                </div>
                <button @click="perfModal = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 space-y-5">
                <template x-if="perfData">
                <div>
                    {{-- Stats Grid --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-500">Satış Adedi</span>
                            <p class="text-xl font-bold text-gray-900" x-text="perfData.stats.total_sales"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-500">Toplam Gelir</span>
                            <p class="text-xl font-bold text-emerald-600" x-text="Number(perfData.stats.total_revenue).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺'"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-500">Ort. Sepet</span>
                            <p class="text-xl font-bold text-brand-600" x-text="Number(perfData.stats.avg_basket).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺'"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-500">Toplam Ürün</span>
                            <p class="text-xl font-bold text-gray-900" x-text="perfData.stats.total_items"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-500">İskonto</span>
                            <p class="text-lg font-bold text-amber-600" x-text="Number(perfData.stats.total_discount).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺'"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-500">Nakit</span>
                            <p class="text-lg font-bold text-green-600" x-text="Number(perfData.stats.cash_total).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺'"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-500">Kart</span>
                            <p class="text-lg font-bold text-purple-600" x-text="Number(perfData.stats.card_total).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺'"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-500">İade / İptal</span>
                            <p class="text-lg font-bold text-red-500" x-text="perfData.stats.refund_count + ' / ' + perfData.stats.cancel_count"></p>
                        </div>
                    </div>

                    {{-- Günlük Satış Grafiği --}}
                    <div class="bg-white rounded-xl p-4 border border-gray-100 mb-5">
                        <h3 class="text-sm font-medium text-gray-700 mb-3"><i class="fas fa-chart-bar text-brand-500 mr-1"></i> Günlük Satışlar (Son 30 Gün)</h3>
                        <div style="position:relative; height:200px;"><canvas id="perfDailyChart"></canvas></div>
                    </div>

                    {{-- En Çok Sattığı Ürünler --}}
                    <div class="bg-white rounded-xl p-4 border border-gray-100" x-show="perfData.top_products && perfData.top_products.length > 0">
                        <h3 class="text-sm font-medium text-gray-700 mb-3"><i class="fas fa-trophy text-amber-500 mr-1"></i> En Çok Sattığı Ürünler</h3>
                        <table class="w-full text-sm">
                            <thead><tr class="border-b border-gray-100">
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">Ürün</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">Adet</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">Tutar</th>
                            </tr></thead>
                            <tbody>
                                <template x-for="p in perfData.top_products" :key="p.product_name">
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2 px-3 text-gray-900" x-text="p.product_name"></td>
                                        <td class="py-2 px-3 text-right text-gray-700" x-text="p.qty"></td>
                                        <td class="py-2 px-3 text-right font-semibold text-emerald-600" x-text="Number(p.total).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                </template>
                <div x-show="!perfData" class="text-center py-16 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2 text-sm">Yükleniyor...</p></div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function staffManager() {
    const emptyForm = () => ({
        name: '', role: '', phone: '', email: '', is_active: true,
        pin: '', permissions: [],
    });

    return {
        showModal: false,
        saving: false,
        editId: null,
        form: emptyForm(),
        perfModal: false,
        perfData: null,
        perfChart: null,

        init() {},

        async showPerformance(staffId) {
            this.perfData = null;
            this.perfModal = true;
            try {
                this.perfData = await posAjax('/staff/' + staffId + '/performance', {}, 'GET');
                this.$nextTick(() => {
                    if (this.perfData.daily_sales && this.perfData.daily_sales.length > 0) {
                        const ctx = document.getElementById('perfDailyChart');
                        if (ctx) {
                            if (this.perfChart) this.perfChart.destroy();
                            this.perfChart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: this.perfData.daily_sales.map(d => {
                                        const dt = new Date(d.date);
                                        return dt.toLocaleDateString('tr-TR', { day: '2-digit', month: 'short' });
                                    }),
                                    datasets: [{
                                        label: 'Satış (₺)',
                                        data: this.perfData.daily_sales.map(d => d.total),
                                        backgroundColor: 'rgba(99,102,241,0.5)',
                                        borderColor: '#6366f1',
                                        borderWidth: 1,
                                        borderRadius: 6,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: { beginAtZero: true, ticks: { color: '#94a3b8', callback: v => '₺' + v.toLocaleString('tr-TR') } },
                                        x: { ticks: { color: '#94a3b8', font: { size: 10 } } }
                                    }
                                }
                            });
                        }
                    }
                });
            } catch (e) {
                showToast('Performans verisi yüklenemedi', 'error');
                this.perfModal = false;
            }
        },

        openModal() {
            this.editId = null;
            this.form = emptyForm();
            this.showModal = true;
        },

        editMember(member) {
            this.editId = member.id;
            let perms = member.permissions;
            if (typeof perms === 'string') {
                try { perms = JSON.parse(perms); } catch { perms = []; }
            }
            this.form = {
                name: member.name,
                role: member.role || '',
                phone: member.phone || '',
                email: member.email || '',
                is_active: !!member.is_active,
                pin: member.pin || '',
                permissions: Array.isArray(perms) ? perms : [],
            };
            this.showModal = true;
        },

        togglePerm(key) {
            const idx = this.form.permissions.indexOf(key);
            if (idx === -1) this.form.permissions.push(key);
            else this.form.permissions.splice(idx, 1);
        },

        selectAllPerms(select) {
            const all = [
                'products.create','products.edit','products.delete',
                'stock.edit',
                'sales.view_all','sales.view_own','sales.refund',
                'customers.create','customers.edit',
                'accounts.tahsilat','accounts.delete',
                'register.open','reports.view',
            ];
            this.form.permissions = select ? [...all] : [];
        },

        async submitForm() {
            if (!this.form.name.trim()) {
                showToast('Ad Soyad zorunlu', 'error');
                return;
            }
            this.saving = true;
            const payload = { ...this.form };
            try {
                if (this.editId) {
                    await posAjax(`/staff/${this.editId}`, payload, 'PUT');
                    showToast('Personel güncellendi', 'success');
                } else {
                    await posAjax('{{ route("pos.staff.store") }}', payload, 'POST');
                    showToast('Personel eklendi', 'success');
                }
                this.showModal = false;
                setTimeout(() => window.location.reload(), 600);
            } catch {
                showToast('Bir hata oluştu', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteMember(id) {
            if (!confirm('Bu personeli silmek istediğinize emin misiniz?')) return;
            try {
                await posAjax(`/staff/${id}`, {}, 'DELETE');
                showToast('Personel silindi', 'success');
                setTimeout(() => window.location.reload(), 500);
            } catch {
                showToast('Silme işlemi başarısız', 'error');
            }
        },
    };
}
</script>
@endpush
@endsection
