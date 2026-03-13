@extends('pos.layouts.app')

@section('title', 'Müşteriler')

@section('content')
<div x-data="customerManager()" x-cloak>
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Toplam Müşteri --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Toplam Müşteri</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $customers->total() }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        {{-- Toplam Borç --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Toplam Borç</p>
                    <p class="text-2xl font-bold text-red-500 mt-1">{{ formatCurrency($customers->getCollection()->where('balance', '<', 0)->sum('balance')) }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                </div>
            </div>
        </div>
        {{-- Toplam Alacak --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Toplam Alacak</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ formatCurrency($customers->getCollection()->where('balance', '>', 0)->sum('balance')) }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>
        {{-- Aktif Müşteri --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Aktif Müşteri</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $customers->getCollection()->where('is_active', true)->count() }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Bar --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Müşteriler</h1>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 md:justify-end">
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.400ms="applySearch()"
                       placeholder="Müşteri ara (ad, telefon, vergi no)..."
                       class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl pl-10 pr-4 py-2.5 w-full sm:w-80 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select x-model="groupFilter" @change="applySearch()"
                    class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                <option value="">Tüm Gruplar</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->customers_count }})</option>
                @endforeach
            </select>
            <button @click="showGroupPanel = !showGroupPanel"
                    class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium rounded-xl text-sm px-4 py-2.5 transition-colors flex items-center gap-2">
                <i class="fas fa-layer-group"></i> Gruplar
            </button>
            <button @click="openCreate()"
                    class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white font-semibold rounded-xl text-sm px-5 py-2.5 transition-all flex items-center gap-2 justify-center whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Müşteri
            </button>
        </div>
    </div>

    {{-- Grup Yönetim Paneli --}}
    <div x-show="showGroupPanel" x-transition class="bg-white rounded-2xl border border-gray-200 p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900"><i class="fas fa-layer-group text-brand-500 mr-2"></i>Müşteri Grupları</h3>
            <button @click="showGroupPanel = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($groups as $g)
                <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2 border border-gray-100">
                    <span class="text-sm text-gray-700">{{ $g->name }}</span>
                    <span class="text-xs text-gray-400">({{ $g->customers_count }})</span>
                    <button @click="editGroup({{ $g->id }}, '{{ addslashes($g->name) }}')" class="text-gray-400 hover:text-yellow-500"><i class="fas fa-pen text-[10px]"></i></button>
                    <button @click="deleteGroup({{ $g->id }})" class="text-gray-400 hover:text-red-500"><i class="fas fa-trash text-[10px]"></i></button>
                </div>
            @endforeach
            <div x-show="!showGroupForm" class="flex items-center">
                <button @click="showGroupForm = true; newGroupName = ''" class="text-sm text-brand-500 hover:text-brand-600"><i class="fas fa-plus mr-1"></i>Yeni Grup</button>
            </div>
        </div>
        <div x-show="showGroupForm" class="flex items-center gap-2">
            <input type="text" x-model="newGroupName" placeholder="Grup adı" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg px-3 py-2 flex-1 focus:border-brand-500">
            <button @click="saveGroup()" :disabled="!newGroupName.trim()" class="px-4 py-2 bg-brand-500 text-white text-sm rounded-lg hover:bg-brand-600 disabled:opacity-50">
                <span x-text="editingGroupId ? 'Güncelle' : 'Ekle'"></span>
            </button>
            <button @click="showGroupForm = false; editingGroupId = null" class="px-3 py-2 text-gray-500 hover:text-gray-700 text-sm">Vazgeç</button>
        </div>
    </div>

    {{-- Customer Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-lg shadow-gray-100/50">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3.5">Ad Soyad</th>
                        <th class="px-4 py-3.5">Grup</th>
                        <th class="px-4 py-3.5">Telefon</th>
                        <th class="px-4 py-3.5 hidden md:table-cell">E-posta</th>
                        <th class="px-4 py-3.5 hidden lg:table-cell">Vergi No</th>
                        <th class="px-4 py-3.5 text-right">Kredi Limiti</th>
                                    <th class="px-4 py-3.5 text-right">Bakiye</th>
                        <th class="px-4 py-3.5 text-right hidden md:table-cell">Toplam Satış</th>
                        <th class="px-4 py-3.5 hidden lg:table-cell">Son İşlem</th>
                        <th class="px-4 py-3.5 text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click.stop="openDetail({{ $customer->id }})">
                            {{-- Name --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-semibold
                                        {{ $customer->type === 'corporate' ? 'bg-purple-50 text-purple-600 border border-purple-200' : 'bg-brand-50 text-brand-600 border border-brand-200' }}">
                                        {{ mb_substr($customer->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $customer->name }}</p>
                                        @if($customer->type === 'corporate')
                                            <span class="text-xs text-purple-600">Kurumsal</span>
                                        @else
                                            <span class="text-xs text-gray-500">Bireysel</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            {{-- Group --}}
                            <td class="px-4 py-3">
                                @if($customer->group)
                                    <span class="text-xs bg-purple-500/10 text-purple-600 px-2 py-0.5 rounded-full">{{ $customer->group->name }}</span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            {{-- Phone --}}
                            <td class="px-4 py-3">
                                @if($customer->phones->isNotEmpty())
                                    @foreach($customer->phones->take(2) as $ph)
                                        <div class="flex items-center gap-1">
                                            <span class="text-gray-700 text-xs">{{ $ph->phone }}</span>
                                            <span class="text-[10px] px-1 rounded {{ $ph->type === 'mobile' ? 'bg-blue-50 text-blue-600' : ($ph->type === 'landline' ? 'bg-gray-100 text-gray-500' : 'bg-purple-50 text-purple-600') }}">
                                                {{ $ph->type === 'mobile' ? 'H' : ($ph->type === 'landline' ? 'S' : 'D') }}
                                            </span>
                                            @if($ph->is_primary) <i class="fas fa-star text-[9px] text-yellow-400"></i> @endif
                                        </div>
                                    @endforeach
                                    @if($customer->phones->count() > 2)
                                        <span class="text-xs text-gray-400">+{{ $customer->phones->count() - 2 }} daha</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            {{-- Email --}}
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-gray-500">{{ $customer->email ?? '-' }}</span>
                            </td>
                            {{-- Tax Number --}}
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span class="font-mono text-xs text-gray-500">{{ $customer->tax_number ?? '-' }}</span>
                            </td>
                            {{-- Credit Limit --}}
                            <td class="px-4 py-3 text-right">
                                <span class="font-mono font-medium text-sky-600">
                                    {{ formatCurrency($customer->credit_limit ?? 0) }}
                                </span>
                            </td>
                            {{-- Balance --}}
                            <td class="px-4 py-3 text-right">
                                @php $balance = $customer->balance ?? 0; @endphp
                                <span class="font-mono font-medium {{ $balance < 0 ? 'text-red-500' : ($balance > 0 ? 'text-emerald-600' : 'text-gray-500') }}">
                                    {{ formatCurrency($balance) }}
                                </span>
                                @if($balance < 0)
                                    <p class="text-xs text-red-500 mt-0.5">Borçlu</p>
                                @elseif($balance > 0)
                                    <p class="text-xs text-emerald-500 mt-0.5">Alacaklı</p>
                                @endif
                            </td>
                            {{-- Total Sales --}}
                            <td class="px-4 py-3 text-right font-mono hidden md:table-cell">
                                {{ formatCurrency($customer->sales_sum_grand_total ?? 0) }}
                            </td>
                            {{-- Last Transaction --}}
                            <td class="px-4 py-3 hidden lg:table-cell">
                                @if($customer->sales_max_sold_at)
                                    <span class="text-gray-500 text-xs">{{ \Carbon\Carbon::parse($customer->sales_max_sold_at)->diffForHumans() }}</span>
                                @else
                                    <span class="text-gray-500 text-xs">-</span>
                                @endif
                            </td>
                            {{-- Actions --}}
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- View Detail --}}
                                    <button @click.stop="openDetail({{ $customer->id }})"
                                       class="p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-xl transition-colors"
                                       title="Detay">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    {{-- Edit --}}
                                    <button @click="openEdit({{ json_encode([
                                                'id' => $customer->id,
                                                'name' => $customer->name,
                                                'customer_group_id' => $customer->customer_group_id,
                                                'phone' => $customer->phone,
                                                'phones' => $customer->phones->map(fn($p) => ['phone' => $p->phone, 'type' => $p->type, 'is_primary' => $p->is_primary])->values()->toArray(),
                                                'email' => $customer->email,
                                                'tax_number' => $customer->tax_number,
                                                'tax_office' => $customer->tax_office,
                                                'address' => $customer->address,
                                                'type' => $customer->type,
                                                'notes' => $customer->notes,
                                            ]) }})"
                                            class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition-colors"
                                            title="Düzenle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    {{-- Ödeme Al --}}
                                    <button @click="openCollect({{ $customer->id }}, '{{ addslashes($customer->name) }}', {{ $customer->balance ?? 0 }})"
                                            class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-colors"
                                            title="Ödeme Al">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </button>
                                    {{-- Borç Ekle --}}
                                    <button @click="openDebt({{ $customer->id }}, '{{ addslashes($customer->name) }}', {{ $customer->balance ?? 0 }})"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors"
                                            title="Borç Ekle">
                                        <i class="fas fa-user-minus w-4 text-center"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <p class="text-gray-500 text-sm">Henüz müşteri eklenmemiş</p>
                                    <button @click="openCreate()" class="text-brand-500 hover:text-brand-600 text-sm font-semibold">
                                        + İlk müşteriyi ekle
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    {{-- Müşteri Detay Modalı --}}
    <div x-show="showDetailModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;" x-cloak>
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showDetailModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between shrink-0">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-user-group text-brand-500"></i>
                    <span x-text="detailData?.customer?.name || 'Müşteri Detayı'"></span>
                </h2>
                <button @click="showDetailModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <div x-show="detailLoading" class="flex items-center justify-center py-16">
                    <i class="fas fa-spinner fa-spin text-2xl text-brand-500"></i>
                </div>

                <template x-if="detailData && !detailLoading">
                    <div>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Bakiye</p>
                                <p class="text-lg font-bold" :class="(detailData.customer.balance||0) < 0 ? 'text-red-500' : ((detailData.customer.balance||0) > 0 ? 'text-emerald-600' : 'text-gray-800')" x-text="formatCurrency(detailData.customer.balance || 0)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Kredi Limiti</p>
                                <p class="text-lg font-bold text-sky-600" x-text="formatCurrency(detailData.customer.credit_limit || 0)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Toplam Satış</p>
                                <p class="text-lg font-bold text-gray-800" x-text="formatCurrency((detailData.recent_sales||[]).reduce((sum, sale) => sum + parseFloat(sale.grand_total || 0), 0))"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Satış Sayısı</p>
                                <p class="text-lg font-bold text-gray-800" x-text="(detailData.recent_sales||[]).length"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Hareket Sayısı</p>
                                <p class="text-lg font-bold text-gray-800" x-text="(detailData.transactions||[]).length"></p>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl border border-gray-100 p-3 mb-4">
                            <div class="flex flex-wrap gap-3 items-center">
                                <template x-for="(ph, i) in (detailData.phones||[])" :key="i">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold rounded px-1.5 py-0.5"
                                              :class="ph.type === 'mobile' ? 'bg-blue-100 text-blue-700' : (ph.type === 'landline' ? 'bg-gray-200 text-gray-600' : 'bg-purple-100 text-purple-700')"
                                              x-text="ph.type === 'mobile' ? 'H' : (ph.type === 'landline' ? 'S' : 'D')"></span>
                                        <span class="text-sm text-gray-800" x-text="ph.phone"></span>
                                        <span x-show="ph.is_primary" class="text-yellow-500 text-xs">★</span>
                                    </div>
                                </template>
                                <template x-if="!(detailData.phones||[]).length">
                                    <span class="text-xs text-gray-400">Telefon yok</span>
                                </template>
                                <span class="text-gray-300">|</span>
                                <span class="text-xs text-gray-500" x-text="detailData.customer.email || ''"></span>
                                <span x-show="detailData.customer.tax_number" class="text-xs text-gray-500">VN: <span x-text="detailData.customer.tax_number" class="font-mono"></span></span>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-4 shadow-sm">
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Rapor Gönder</p>
                                        <p class="text-xs text-gray-500" x-text="selectedCustomerSaleIds.length ? selectedCustomerSaleIds.length + ' fiş seçili' : 'Tek fiş, seçili birkaç fiş veya tüm geçmiş gönderilebilir.'"></p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="toggleAllCustomerSales(true)" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Tümünü Seç</button>
                                        <button type="button" @click="toggleAllCustomerSales(false)" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Seçimi Temizle</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-3">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700 mb-2">Seçili Fişler</p>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="sendCustomerReportWhatsApp('selected')" class="px-3 py-2 text-xs font-bold rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 transition-colors"><i class="fab fa-whatsapp mr-1.5"></i>WhatsApp</button>
                                            <button type="button" @click="sendCustomerReportEmail('selected')" class="px-3 py-2 text-xs font-bold rounded-xl bg-sky-500 text-white hover:bg-sky-600 transition-colors"><i class="fas fa-envelope mr-1.5"></i>E-posta</button>
                                            <button type="button" @click="printCustomerReport('selected')" class="px-3 py-2 text-xs font-bold rounded-xl bg-gray-900 text-white hover:bg-black transition-colors"><i class="fas fa-print mr-1.5"></i>Yazdır</button>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-purple-100 bg-purple-50/50 p-3">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-700 mb-2">Tüm Geçmiş</p>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="sendCustomerReportWhatsApp('all')" class="px-3 py-2 text-xs font-bold rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 transition-colors"><i class="fab fa-whatsapp mr-1.5"></i>WhatsApp</button>
                                            <button type="button" @click="sendCustomerReportEmail('all')" class="px-3 py-2 text-xs font-bold rounded-xl bg-sky-500 text-white hover:bg-sky-600 transition-colors"><i class="fas fa-envelope mr-1.5"></i>E-posta</button>
                                            <button type="button" @click="printCustomerReport('all')" class="px-3 py-2 text-xs font-bold rounded-xl bg-gray-900 text-white hover:bg-black transition-colors"><i class="fas fa-print mr-1.5"></i>Yazdır</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <button @click="detailTab = 'all'" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                    :class="detailTab === 'all' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                <i class="fas fa-stream mr-1"></i>Tüm Hareketler
                            </button>
                            <button @click="detailTab = 'sales'" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                    :class="detailTab === 'sales' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                <i class="fas fa-shopping-cart mr-1"></i>Satışlar
                                <span class="ml-1 bg-gray-200 text-gray-600 rounded-full px-1.5 py-0.5 text-[10px]" x-text="(detailData.recent_sales||[]).length"></span>
                            </button>
                            <button @click="detailTab = 'transactions'" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                    :class="detailTab === 'transactions' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                <i class="fas fa-coins mr-1"></i>Hesap Hareketleri
                                <span class="ml-1 bg-gray-200 text-gray-600 rounded-full px-1.5 py-0.5 text-[10px]" x-text="(detailData.transactions||[]).length"></span>
                            </button>
                            <button @click="detailTab = 'notes'" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                    :class="detailTab === 'notes' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                                Notlar
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <button @click="openCollect(detailData.customer.id, detailData.customer.name, detailData.customer.balance); showDetailModal=false"
                                    class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-medium rounded-lg flex items-center gap-1.5">
                                <i class="fas fa-hand-holding-dollar"></i> Ödeme Al
                            </button>
                            <button @click="openDebt(detailData.customer.id, detailData.customer.name, detailData.customer.balance); showDetailModal=false"
                                    class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg flex items-center gap-1.5">
                                <i class="fas fa-user-minus"></i> Borç Ekle
                            </button>
                        </div>

                    {{-- TÜMÜ GÖRÜNÜMÜ: Ağaç Zaman Çizelgesi --}}
                    <div x-show="detailTab==='all'">
                        <template x-if="(!detailData?.recent_sales?.length && !detailData?.transactions?.length)">
                            <div class="text-center py-12 text-gray-400"><i class="fas fa-inbox text-3xl mb-2"></i><p class="text-sm">Henüz hareket yok</p></div>
                        </template>
                        {{-- Birleşik, tarih sıralı zaman çizelgesi --}}
                        <template x-for="item in detailTimeline" :key="item._key">
                            {{-- SATIŞ SATIRI --}}
                            <div x-show="item._type==='sale'" class="mb-2">
                                <div @click="toggleSaleDetails(item)"
                                     class="w-full flex items-center gap-3 p-3 rounded-xl border border-gray-100 bg-white hover:border-brand-200 hover:shadow-sm hover:shadow-brand-500/10 cursor-pointer text-left group transition-all">
                                    <label class="flex items-center" @click.stop>
                                        <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" :checked="selectedCustomerSaleIds.includes(item.id)" @change="toggleCustomerSaleSelection(item.id)">
                                    </label>
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform shadow-sm"
                                         :class="item.payment_method==='cash'?'bg-emerald-50 text-emerald-600':item.payment_method==='card'?'bg-blue-50 text-blue-600':item.payment_method==='credit'?'bg-amber-50 text-amber-600':'bg-purple-50 text-purple-600'">
                                        <i :class="item.payment_method==='cash'?'fas fa-money-bill-wave':item.payment_method==='card'?'fas fa-credit-card':item.payment_method==='credit'?'fas fa-user-clock':'fas fa-layer-group'" class="text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-[13px] text-gray-900" x-text="item.receipt_no || ('#' + item.id)"></span>
                                            <span class="text-[11px] px-1.5 py-0.5 rounded-md font-medium"
                                                  :class="item.payment_method==='cash'?'bg-emerald-100 text-emerald-700':item.payment_method==='card'?'bg-blue-100 text-blue-700':item.payment_method==='credit'?'bg-amber-100 text-amber-700':'bg-purple-100 text-purple-700'"
                                                  x-text="item.payment_method==='cash'?'Nakit':item.payment_method==='card'?'Kart':item.payment_method==='credit'?'Veresiye':'Karışık'">
                                            </span>
                                            <span x-show="item.items?.length" class="text-[11px] text-gray-400" x-text="(item.items?.length||0) + ' ürün'"></span>
                                        </div>
                                        <span class="text-[11px] text-gray-400" x-text="item.sold_at ? new Date(item.sold_at).toLocaleDateString('tr-TR',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-'"></span>
                                    </div>
                                    <div class="text-right flex-shrink-0 flex items-center gap-2">
                                        <div>
                                            <p class="font-black text-[15px] text-gray-900" x-text="formatCurrency(item.grand_total)"></p>
                                            <p class="text-[11px] text-gray-400">Satış</p>
                                        </div>
                                        <i class="fas text-gray-300 text-xs group-hover:text-brand-400 group-hover:translate-x-0.5 transition-all"
                                           :class="expandedSaleKey === item._key ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                    </div>
                                </div>
                                <div x-show="expandedSaleKey === item._key" x-transition class="mt-2 rounded-2xl border border-brand-100 bg-brand-50/40 p-4" :id="'customer-invoice-print-' + item._key">
                                    <div class="flex items-start justify-between gap-3 mb-4">
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1" x-text="detailData?.customer?.name"></p>
                                            <div class="text-2xl font-black text-gray-900 tracking-tight" x-text="formatCurrency(item.grand_total)"></div>
                                            <div class="mt-2 flex items-center gap-2 flex-wrap">
                                                <span class="text-xs px-2 py-0.5 rounded-lg font-bold"
                                                      :class="item.payment_method==='cash'?'bg-emerald-100 text-emerald-700':item.payment_method==='card'?'bg-blue-100 text-blue-700':item.payment_method==='credit'?'bg-amber-100 text-amber-700':'bg-purple-100 text-purple-700'"
                                                      x-text="item.payment_method==='cash'?'Nakit':item.payment_method==='card'?'Kart':item.payment_method==='credit'?'Veresiye':'Karışık'"></span>
                                                <span class="text-xs text-gray-400" x-text="item.sold_at ? new Date(item.sold_at).toLocaleString('tr-TR',{day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'}) : ''"></span>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap justify-end gap-2 shrink-0">
                                            <button type="button" @click="sendCustomerReportWhatsApp('single', item)" class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                                            <button type="button" @click="sendCustomerReportEmail('single', item)" class="px-3 py-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fas fa-envelope"></i> E-posta</button>
                                            <button type="button" @click="printCustomerReport('single', item)" class="px-3 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fas fa-print"></i> Yazdır</button>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-3">
                                        <div class="divide-y divide-gray-50">
                                            <template x-for="si in (item.items||[])" :key="si.id">
                                                <div class="p-4 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0" x-text="si.quantity"></div>
                                                        <div class="min-w-0">
                                                            <div class="text-[13px] font-bold text-gray-800 truncate" x-text="si.product_name"></div>
                                                            <div class="text-[11px] text-gray-400 mt-0.5" x-text="'Birim: ' + formatCurrency(si.unit_price)"></div>
                                                        </div>
                                                    </div>
                                                    <div class="text-right ml-3 flex-shrink-0">
                                                        <div class="text-[14px] font-black text-gray-900" x-text="formatCurrency(si.total || si.total_price || (si.unit_price * si.quantity))"></div>
                                                    </div>
                                                </div>
                                            </template>
                                            <div x-show="!(item.items||[]).length" class="p-8 text-center text-gray-400 text-sm">
                                                <i class="fas fa-box-open text-2xl mb-2"></i>
                                                <p>Ürün detayı bulunamadı</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-500">Ürün Sayısı</span>
                                            <span class="font-bold text-gray-900" x-text="(item.items||[]).reduce((s,i)=>s+(i.quantity||1),0) + ' adet'"></span>
                                        </div>
                                        <template x-if="item.discount_amount > 0">
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">İndirim</span>
                                                <span class="text-red-500 font-bold" x-text="'-' + formatCurrency(item.discount_amount)"></span>
                                            </div>
                                        </template>
                                        <template x-if="item.service_fee > 0">
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-500">Servis Ücreti</span>
                                                <span class="font-bold text-gray-900" x-text="formatCurrency(item.service_fee)"></span>
                                            </div>
                                        </template>
                                        <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
                                            <span class="text-base font-bold text-gray-800">Genel Toplam</span>
                                            <span class="text-xl font-black text-gray-900" x-text="formatCurrency(item.grand_total)"></span>
                                        </div>
                                    </div>

                                    <div x-show="item.notes" class="mt-3 bg-amber-50 border border-amber-100 rounded-xl p-4 text-sm text-amber-700">
                                        <i class="fas fa-sticky-note mr-2"></i>
                                        <span x-text="item.notes"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- HESAP HAREKETİ SATIRI --}}
                            <div x-show="item._type==='tx'" class="mb-2">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                             :class="item.amount >= 0 ? 'bg-emerald-500' : 'bg-red-500'">
                                            <i :class="item.type==='payment'?'fas fa-hand-holding-dollar':item.type==='debt'?'fas fa-user-minus':'fas fa-exchange-alt'"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-sm font-medium text-gray-800" x-text="item.description || (item.type==='payment'?'Tahsilat':item.type==='debt'?'Borç Eklendi':'Hesap Hareketi')"></p>
                                                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                                                      :class="item.type==='payment'?'bg-emerald-100 text-emerald-700':item.type==='debt'?'bg-red-100 text-red-700':'bg-gray-100 text-gray-600'"
                                                      x-text="item.type==='payment'?'Ödeme':item.type==='debt'?'Borç':'Hareket'">
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-400" x-text="item.transaction_date ? new Date(item.transaction_date).toLocaleDateString('tr-TR',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : new Date(item.created_at).toLocaleDateString('tr-TR',{day:'2-digit',month:'short',year:'numeric'})"></p>
                                        </div>
                                    </div>
                                    <div class="text-right ml-3 flex-shrink-0">
                                        <p class="text-sm font-bold" :class="item.amount>=0?'text-emerald-600':'text-red-500'" x-text="(item.amount>=0?'+':'') + formatCurrency(Math.abs(item.amount))"></p>
                                        <p class="text-xs text-gray-400" x-text="'Bakiye: ' + formatCurrency(item.balance_after)"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="detailTab==='sales'">
                        <template x-if="detailData?.recent_sales?.length === 0">
                            <div class="text-center py-12 text-gray-400"><i class="fas fa-shopping-cart text-3xl mb-2"></i><p class="text-sm">Henüz satış yok</p></div>
                        </template>
                        <template x-for="sale in (detailData?.recent_sales || [])" :key="sale.id">
                            <div class="mb-2">
                            <div @click="toggleSaleDetails(sale)"
                                 class="w-full mb-2 flex items-center gap-3 p-3 rounded-xl border border-gray-100 bg-white hover:border-brand-200 hover:shadow-sm hover:shadow-brand-500/10 cursor-pointer text-left group transition-all">
                                <label class="flex items-center" @click.stop>
                                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" :checked="selectedCustomerSaleIds.includes(sale.id)" @change="toggleCustomerSaleSelection(sale.id)">
                                </label>
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform shadow-sm"
                                     :class="sale.payment_method==='cash'?'bg-emerald-50 text-emerald-600':sale.payment_method==='card'?'bg-blue-50 text-blue-600':sale.payment_method==='credit'?'bg-amber-50 text-amber-600':'bg-purple-50 text-purple-600'">
                                    <i :class="sale.payment_method==='cash'?'fas fa-money-bill-wave':sale.payment_method==='card'?'fas fa-credit-card':sale.payment_method==='credit'?'fas fa-user-clock':'fas fa-layer-group'" class="text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-[13px] text-gray-900" x-text="sale.receipt_no || ('#' + sale.id)"></span>
                                        <span class="text-[11px] px-1.5 py-0.5 rounded-md font-medium"
                                              :class="sale.payment_method==='cash'?'bg-emerald-100 text-emerald-700':sale.payment_method==='card'?'bg-blue-100 text-blue-700':sale.payment_method==='credit'?'bg-amber-100 text-amber-700':'bg-purple-100 text-purple-700'"
                                              x-text="sale.payment_method==='cash'?'Nakit':sale.payment_method==='card'?'Kart':sale.payment_method==='credit'?'Veresiye':'Karışık'">
                                        </span>
                                        <span class="text-[11px] text-gray-400" x-text="(sale.items?.length||0) + ' ürün'"></span>
                                    </div>
                                    <span class="text-[11px] text-gray-400" x-text="sale.sold_at ? new Date(sale.sold_at).toLocaleDateString('tr-TR',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-'"></span>
                                </div>
                                <div class="text-right flex-shrink-0 flex items-center gap-2">
                                    <div>
                                        <p class="font-black text-[15px] text-gray-900" x-text="formatCurrency(sale.grand_total)"></p>
                                    </div>
                                    <i class="fas text-gray-300 text-xs group-hover:text-brand-400 group-hover:translate-x-0.5 transition-all"
                                       :class="expandedSaleKey === ('sale_' + sale.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                </div>
                            </div>
                            <div x-show="expandedSaleKey === ('sale_' + sale.id)" x-transition class="mt-2 rounded-2xl border border-brand-100 bg-brand-50/40 p-4" :id="'customer-invoice-print-sale-' + sale.id">
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1" x-text="detailData?.customer?.name"></p>
                                        <div class="text-2xl font-black text-gray-900 tracking-tight" x-text="formatCurrency(sale.grand_total)"></div>
                                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                                            <span class="text-xs px-2 py-0.5 rounded-lg font-bold"
                                                  :class="sale.payment_method==='cash'?'bg-emerald-100 text-emerald-700':sale.payment_method==='card'?'bg-blue-100 text-blue-700':sale.payment_method==='credit'?'bg-amber-100 text-amber-700':'bg-purple-100 text-purple-700'"
                                                  x-text="sale.payment_method==='cash'?'Nakit':sale.payment_method==='card'?'Kart':sale.payment_method==='credit'?'Veresiye':'Karışık'"></span>
                                            <span class="text-xs text-gray-400" x-text="sale.sold_at ? new Date(sale.sold_at).toLocaleString('tr-TR',{day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'}) : ''"></span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap justify-end gap-2 shrink-0">
                                        <button type="button" @click="sendCustomerReportWhatsApp('single', sale)" class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                                        <button type="button" @click="sendCustomerReportEmail('single', sale)" class="px-3 py-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fas fa-envelope"></i> E-posta</button>
                                        <button type="button" @click="printCustomerReport('single', sale)" class="px-3 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors"><i class="fas fa-print"></i> Yazdır</button>
                                    </div>
                                </div>

                                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-3">
                                    <div class="divide-y divide-gray-50">
                                        <template x-for="si in (sale.items||[])" :key="si.id">
                                            <div class="p-4 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0" x-text="si.quantity"></div>
                                                    <div class="min-w-0">
                                                        <div class="text-[13px] font-bold text-gray-800 truncate" x-text="si.product_name"></div>
                                                        <div class="text-[11px] text-gray-400 mt-0.5" x-text="'Birim: ' + formatCurrency(si.unit_price)"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right ml-3 flex-shrink-0">
                                                    <div class="text-[14px] font-black text-gray-900" x-text="formatCurrency(si.total || si.total_price || (si.unit_price * si.quantity))"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="!(sale.items||[]).length" class="p-8 text-center text-gray-400 text-sm">
                                            <i class="fas fa-box-open text-2xl mb-2"></i>
                                            <p>Ürün detayı bulunamadı</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-500">Ürün Sayısı</span>
                                        <span class="font-bold text-gray-900" x-text="(sale.items||[]).reduce((s,i)=>s+(i.quantity||1),0) + ' adet'"></span>
                                    </div>
                                    <template x-if="sale.discount_amount > 0">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-500">İndirim</span>
                                            <span class="text-red-500 font-bold" x-text="'-' + formatCurrency(sale.discount_amount)"></span>
                                        </div>
                                    </template>
                                    <template x-if="sale.service_fee > 0">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-500">Servis Ücreti</span>
                                            <span class="font-bold text-gray-900" x-text="formatCurrency(sale.service_fee)"></span>
                                        </div>
                                    </template>
                                    <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
                                        <span class="text-base font-bold text-gray-800">Genel Toplam</span>
                                        <span class="text-xl font-black text-gray-900" x-text="formatCurrency(sale.grand_total)"></span>
                                    </div>
                                </div>

                                <div x-show="sale.notes" class="mt-3 bg-amber-50 border border-amber-100 rounded-xl p-4 text-sm text-amber-700">
                                    <i class="fas fa-sticky-note mr-2"></i>
                                    <span x-text="sale.notes"></span>
                                </div>
                            </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="detailTab==='transactions'">
                        <template x-if="detailData?.transactions?.length === 0">
                            <div class="text-center py-12 text-gray-400"><i class="fas fa-exchange-alt text-3xl mb-2"></i><p class="text-sm">Henüz hareket yok</p></div>
                        </template>
                        <div class="space-y-2">
                            <template x-for="tx in (detailData?.transactions || [])" :key="tx.id">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs flex-shrink-0"
                                             :class="tx.amount >= 0 ? 'bg-emerald-500' : 'bg-red-500'">
                                            <i :class="tx.type==='payment'?'fas fa-hand-holding-dollar':tx.type==='debt'?'fas fa-user-minus':'fas fa-exchange-alt'"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-sm font-medium text-gray-800" x-text="tx.description || (tx.type==='payment'?'Tahsilat':tx.type==='debt'?'Borç Eklendi':'İşlem')"></p>
                                                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                                                      :class="tx.type==='payment'?'bg-emerald-100 text-emerald-700':tx.type==='debt'?'bg-red-100 text-red-700':'bg-gray-100 text-gray-600'"
                                                      x-text="tx.type==='payment'?'Ödeme':tx.type==='debt'?'Borç':'Hareket'">
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-400" x-text="tx.transaction_date ? new Date(tx.transaction_date).toLocaleDateString('tr-TR',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : new Date(tx.created_at).toLocaleDateString('tr-TR',{day:'2-digit',month:'short',year:'numeric'})"></p>
                                        </div>
                                    </div>
                                    <div class="text-right ml-3 flex-shrink-0">
                                        <p class="text-sm font-bold" :class="tx.amount>=0?'text-emerald-600':'text-red-500'" x-text="(tx.amount>=0?'+':'') + formatCurrency(Math.abs(tx.amount))"></p>
                                        <p class="text-xs text-gray-400" x-text="'Bakiye: ' + formatCurrency(tx.balance_after)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="detailTab==='notes'">
                        <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 text-sm text-gray-700 whitespace-pre-line min-h-[80px]" x-text="detailData.customer.notes || 'Not bulunmuyor.'"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Borç Ekle Modal --}}
    <div x-show="showDebtModal"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showDebtModal = false"></div>
        <div x-show="showDebtModal"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl border border-gray-200 shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900"><i class="fas fa-user-minus mr-2 text-red-500"></i>Borç Ekle</h2>
                <button @click="showDebtModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submitDebt()" class="p-6 space-y-5">
                <div class="bg-red-50 rounded-xl border border-red-100 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Müşteri</p>
                            <p class="text-gray-900 font-medium mt-0.5" x-text="debtCustomerName"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Mevcut Bakiye</p>
                            <p class="font-mono font-semibold mt-0.5" :class="debtCustomerBalance < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="formatCurrency(debtCustomerBalance)"></p>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Borç Tutarı <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" x-model="debtForm.amount" step="0.01" min="0.01" required
                               class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl pl-4 pr-8 py-3 focus:ring-2 focus:ring-red-500/20 focus:border-red-400 text-lg font-mono"
                               placeholder="0.00" x-ref="debtAmountInput">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-semibold">₺</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Açıklama</label>
                    <input type="text" x-model="debtForm.description"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-red-500/20 focus:border-red-400"
                           placeholder="Borç nedeni (opsiyonel)">
                </div>
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" @click="showDebtModal = false"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl transition-colors">
                        İptal
                    </button>
                    <button type="submit" :disabled="addingDebt"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                        <svg x-show="addingDebt" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="addingDebt ? 'Kaydediliyor...' : 'Borç Ekle'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Customer Form Modal --}}
    <div x-show="showFormModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeFormModal()"></div>

        <div x-show="showFormModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl border border-gray-200 shadow-lg shadow-gray-100/50 w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Modal Header --}}
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10 rounded-t-2xl">
                <h2 class="text-lg font-semibold text-gray-900" x-text="editingId ? 'Müşteri Düzenle' : 'Yeni Müşteri'"></h2>
                <button @click="closeFormModal()" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <form @submit.prevent="submitForm()" class="p-6 space-y-5">
                {{-- Customer Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Müşteri Tipi</label>
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" x-model="form.type" value="individual" class="peer hidden">
                            <div class="peer-checked:border-brand-500 peer-checked:bg-brand-50 border border-gray-200 rounded-xl p-3 text-center transition-colors hover:bg-gray-50">
                                <svg class="w-5 h-5 mx-auto mb-1 text-gray-500 peer-checked:text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-sm text-gray-700">Bireysel</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" x-model="form.type" value="corporate" class="peer hidden">
                            <div class="peer-checked:border-purple-500 peer-checked:bg-purple-50 border border-gray-200 rounded-xl p-3 text-center transition-colors hover:bg-gray-50">
                                <svg class="w-5 h-5 mx-auto mb-1 text-gray-500 peer-checked:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span class="text-sm text-gray-700">Kurumsal</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ad Soyad / Firma Adı <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" required
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                           placeholder="Müşteri adını girin">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Müşteri Grubu</label>
                    <select x-model="form.customer_group_id" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">Grup Seç (Opsiyonel)</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Multi-Phone --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-sm font-medium text-gray-700">Telefon Numaraları</label>
                        <button type="button" @click="addPhone()"
                                class="text-xs text-brand-600 hover:text-brand-700 font-semibold flex items-center gap-1">
                            <i class="fas fa-plus"></i> Telefon Ekle
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(p, idx) in form.phones" :key="idx">
                            <div class="flex items-center gap-2">
                                <select x-model="p.type"
                                        class="bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-xl px-2 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 w-28 flex-shrink-0">
                                    <option value="mobile">Hareketli</option>
                                    <option value="landline">Sabit</option>
                                    <option value="other">Diğer</option>
                                </select>
                                <input type="tel" x-model="p.phone"
                                       class="flex-1 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                                       placeholder="Telefon numarası">
                                <button type="button" @click="setPrimaryPhone(idx)"
                                        :title="p.is_primary ? 'Ana numara' : 'Ana numara yap'"
                                        :class="p.is_primary ? 'text-yellow-500 bg-yellow-50' : 'text-gray-300 hover:text-yellow-400'"
                                        class="p-2 rounded-xl border border-gray-200 flex-shrink-0">
                                    <i class="fas fa-star text-xs"></i>
                                </button>
                                <button type="button" @click="removePhone(idx)" x-show="form.phones.length > 1"
                                        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl border border-gray-200 flex-shrink-0">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta</label>
                    <input type="email" x-model="form.email"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                           placeholder="ornek@email.com">
                </div>

                {{-- Tax Number & Tax Office --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="form.type === 'corporate'" x-transition>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Vergi Numarası</label>
                        <input type="text" x-model="form.tax_number"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="Vergi numarası">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Vergi Dairesi</label>
                        <input type="text" x-model="form.tax_office"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="Vergi dairesi">
                    </div>
                </div>

                {{-- Address --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Adres</label>
                    <textarea x-model="form.address" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 resize-none"
                              placeholder="Müşteri adresi"></textarea>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Notlar</label>
                    <textarea x-model="form.notes" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 resize-none"
                              placeholder="Ek notlar..."></textarea>
                </div>

                {{-- Submit --}}
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" @click="closeFormModal()"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl transition-colors">
                        İptal
                    </button>
                    <button type="submit"
                            :disabled="saving"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-900 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <svg x-show="saving" class="animate-spin h-4 w-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="editingId ? 'Güncelle' : 'Kaydet'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tahsilat Modal --}}
    <div x-show="showCollectModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showCollectModal = false"></div>

        <div x-show="showCollectModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl border border-gray-200 shadow-lg shadow-gray-100/50 w-full max-w-md max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900"><i class="fas fa-hand-holding-dollar mr-2 text-emerald-500"></i>Ödeme Al</h2>
                <button @click="showCollectModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <form @submit.prevent="submitCollect()" class="p-6 space-y-5">
                {{-- Customer Info --}}
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Müşteri</p>
                            <p class="text-gray-900 font-medium mt-0.5" x-text="collectCustomerName"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Mevcut Bakiye</p>
                            <p class="font-mono font-semibold mt-0.5"
                               :class="collectCustomerBalance < 0 ? 'text-red-500' : (collectCustomerBalance > 0 ? 'text-emerald-600' : 'text-gray-500')"
                               x-text="formatCurrency(collectCustomerBalance)"></p>
                        </div>
                    </div>
                </div>

                {{-- Amount --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tahsilat Tutarı <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" x-model="collectForm.amount" step="0.01" min="0.01" required
                               class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl pl-4 pr-8 py-3 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400 text-lg font-mono"
                               placeholder="0.00">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-semibold">₺</span>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ödeme Yöntemi</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" x-model="collectForm.payment_method" value="cash" class="peer hidden">
                            <div class="peer-checked:border-emerald-500 peer-checked:bg-emerald-50 border border-gray-200 rounded-xl p-3 text-center transition-colors hover:bg-gray-50">
                                <svg class="w-5 h-5 mx-auto mb-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span class="text-xs text-gray-700">Nakit</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" x-model="collectForm.payment_method" value="card" class="peer hidden">
                            <div class="peer-checked:border-brand-500 peer-checked:bg-brand-50 border border-gray-200 rounded-xl p-3 text-center transition-colors hover:bg-gray-50">
                                <svg class="w-5 h-5 mx-auto mb-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <span class="text-xs text-gray-700">Kart</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" x-model="collectForm.payment_method" value="bank_transfer" class="peer hidden">
                            <div class="peer-checked:border-amber-500 peer-checked:bg-amber-50 border border-gray-200 rounded-xl p-3 text-center transition-colors hover:bg-gray-50">
                                <svg class="w-5 h-5 mx-auto mb-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                </svg>
                                <span class="text-xs text-gray-700">Havale</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Açıklama</label>
                    <input type="text" x-model="collectForm.description"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                           placeholder="Tahsilat notu (opsiyonel)">
                </div>

                {{-- Submit --}}
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" @click="showCollectModal = false"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl transition-colors">
                        İptal
                    </button>
                    <button type="submit"
                            :disabled="collecting"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-900 bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <svg x-show="collecting" class="animate-spin h-4 w-4 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Tahsilat Al
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function customerManager() {
    return {
        showFormModal: false,
        showCollectModal: false,
        showDebtModal: false,
        showDetailModal: false,
        showGroupPanel: false,
        showGroupForm: false,
        expandedSaleKey: null,
        selectedCustomerSaleIds: [],
        detailLoading: false,
        detailData: null,
        detailTab: 'all',
        detailExpanded: false,
        editingId: null,
        saving: false,
        collecting: false,
        addingDebt: false,
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        groupFilter: new URLSearchParams(window.location.search).get('group_id') || '',
        newGroupName: '',
        editingGroupId: null,

        collectCustomerId: null,
        collectCustomerName: '',
        collectCustomerBalance: 0,
        debtCustomerId: null,
        debtCustomerName: '',
        debtCustomerBalance: 0,

        form: {
            name: '',
            customer_group_id: '',
            phones: [{phone: '', type: 'mobile', is_primary: true}],
            email: '',
            tax_number: '', credit_limit: null,
            tax_office: '',
            address: '',
            type: 'individual',
            notes: '',
        },

        collectForm: {
            amount: '',
            payment_method: 'cash',
            description: '',
        },
        debtForm: {
            amount: '',
            description: '',
        },

        resetForm() {
            this.form = {
                name: '',
                customer_group_id: '',
                phones: [{phone: '', type: 'mobile', is_primary: true}],
                email: '',
                tax_number: '', credit_limit: null,
                tax_office: '',
                address: '',
                type: 'individual',
                notes: '',
            };
            this.editingId = null;
        },

        resetCollectForm() {
            this.collectForm = { amount: '', payment_method: 'cash', description: '' };
            this.collectCustomerId = null;
            this.collectCustomerName = '';
            this.collectCustomerBalance = 0;
        },

        // ─── Telefon yönetimi ────────────────────────────────
        addPhone() {
            this.form.phones.push({phone: '', type: 'mobile', is_primary: false});
        },
        removePhone(idx) {
            const wasPrimary = this.form.phones[idx].is_primary;
            this.form.phones.splice(idx, 1);
            if (wasPrimary && this.form.phones.length > 0) {
                this.form.phones[0].is_primary = true;
            }
        },
        setPrimaryPhone(idx) {
            this.form.phones.forEach((p, i) => p.is_primary = (i === idx));
        },

        openDebt(id, name, balance) {
            this.debtForm = { amount: '', description: '' };
            this.debtCustomerId = id;
            this.debtCustomerName = name;
            this.debtCustomerBalance = balance;
            this.showDebtModal = true;
            this.$nextTick(() => this.$refs.debtAmountInput?.focus());
        },

        async submitDebt() {
            if (!this.debtForm.amount || this.debtForm.amount <= 0) {
                showToast('Geçerli bir tutar girin.', 'error');
                return;
            }
            this.addingDebt = true;
            const url = '{{ route("pos.customers.debt", ":id") }}'.replace(':id', this.debtCustomerId);
            try {
                await posAjax(url, this.debtForm, 'POST');
                showToast('Borç başarıyla eklendi.', 'success');
                this.showDebtModal = false;
                window.location.reload();
            } catch (e) {
                showToast(e.message || 'Borç eklenemedi.', 'error');
            } finally {
                this.addingDebt = false;
            }
        },

        async openDetail(id) {
            this.detailData = null;
            this.detailLoading = true;
            this.detailTab = 'all';
            this.detailExpanded = false;
            this.expandedSaleKey = null;
            this.selectedCustomerSaleIds = [];
            this.showDetailModal = true;
            try {
                const data = await posAjax('/customers/' + id, {}, 'GET');
                // Her satışa _open ve _type ekle
                (data.recent_sales || []).forEach(s => { s._open = false; s._type = 'sale'; s._key = 'sale_'+s.id; });
                (data.transactions || []).forEach(t => { t._type = 'tx'; t._key = 'tx_'+t.id; });
                this.detailData = data;
            } catch(e) {
                showToast('Detay yüklenemedi.', 'error');
                this.showDetailModal = false;
            } finally {
                this.detailLoading = false;
            }
        },

        toggleSaleDetails(sale) {
            const key = sale._key || ('sale_' + sale.id);
            this.expandedSaleKey = this.expandedSaleKey === key ? null : key;
        },

        toggleCustomerSaleSelection(id) {
            if (this.selectedCustomerSaleIds.includes(id)) {
                this.selectedCustomerSaleIds = this.selectedCustomerSaleIds.filter(selectedId => selectedId !== id);
                return;
            }
            this.selectedCustomerSaleIds = [...this.selectedCustomerSaleIds, id];
        },

        toggleAllCustomerSales(selectAll) {
            if (!selectAll) {
                this.selectedCustomerSaleIds = [];
                return;
            }
            this.selectedCustomerSaleIds = (this.detailData?.recent_sales || []).map(sale => sale.id);
        },

        normalizeWhatsappPhone(phone) {
            const digits = String(phone || '').replace(/\D/g, '');
            if (!digits) return '';
            if (digits.startsWith('90')) return digits;
            if (digits.startsWith('0') && digits.length === 11) return '90' + digits.slice(1);
            if (digits.length === 10) return '90' + digits;
            return digits;
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        formatReportDate(value) {
            if (!value) return '-';
            return new Date(value).toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        getCustomerReportPhone() {
            const phones = this.detailData?.phones || [];
            const rawPhone = phones.find(phone => phone.is_primary)?.phone || phones[0]?.phone || this.detailData?.customer?.phone || '';
            return this.normalizeWhatsappPhone(rawPhone);
        },

        getCustomerReportEmail() {
            return this.detailData?.customer?.email || '';
        },

        resolveCustomerReportSales(scope, sale = null) {
            const sales = this.detailData?.recent_sales || [];
            if (scope === 'single' && sale) return [sale];
            if (scope === 'all') return sales;
            return sales.filter(currentSale => this.selectedCustomerSaleIds.includes(currentSale.id));
        },

        buildCustomerReport(scope, sale = null) {
            if (!this.detailData?.customer) {
                showToast('Rapor verisi bulunamadı.', 'error');
                return null;
            }

            const customer = this.detailData.customer;
            const sales = this.resolveCustomerReportSales(scope, sale);
            if (!sales.length) {
                showToast(scope === 'selected' ? 'Önce en az bir fiş seçin.' : 'Raporlanacak fiş bulunamadı.', 'warning');
                return null;
            }

            const includeAllHistory = scope === 'all';
            const transactions = includeAllHistory ? (this.detailData.transactions || []) : [];
            const total = sales.reduce((sum, currentSale) => sum + parseFloat(currentSale.grand_total || 0), 0);
            const salesPreview = sales.slice(0, 12);
            const txPreview = transactions.slice(0, 8);
            const reportTitle = scope === 'single'
                ? `Fiş Raporu - ${customer.name}`
                : (scope === 'all' ? `Tüm Geçmiş Raporu - ${customer.name}` : `Seçili Fişler Raporu - ${customer.name}`);

            const textLines = [
                reportTitle,
                `Müşteri: ${customer.name}`,
                `Bakiye: ${this.formatCurrency(customer.balance || 0)}`,
                `Kredi Limiti: ${this.formatCurrency(customer.credit_limit || 0)}`,
                `Fiş Sayısı: ${sales.length}`,
                `Toplam Tutar: ${this.formatCurrency(total)}`,
                '',
                'Fişler:',
                ...salesPreview.map(currentSale => `- ${(currentSale.receipt_no || ('#' + currentSale.id))} | ${this.formatReportDate(currentSale.sold_at)} | ${this.formatCurrency(currentSale.grand_total)} | ${currentSale.payment_method === 'cash' ? 'Nakit' : currentSale.payment_method === 'card' ? 'Kart' : currentSale.payment_method === 'credit' ? 'Veresiye' : 'Karışık'}`),
            ];

            if (sales.length > salesPreview.length) {
                textLines.push(`... ve ${sales.length - salesPreview.length} fiş daha`);
            }

            if (includeAllHistory && transactions.length) {
                textLines.push('', 'Son Hesap Hareketleri:');
                txPreview.forEach(transaction => {
                    textLines.push(`- ${transaction.description || transaction.type || 'Hareket'} | ${this.formatReportDate(transaction.transaction_date || transaction.created_at)} | ${(transaction.amount >= 0 ? '+' : '-') + this.formatCurrency(Math.abs(transaction.amount || 0))}`);
                });
                if (transactions.length > txPreview.length) {
                    textLines.push(`... ve ${transactions.length - txPreview.length} hareket daha`);
                }
            }

            const salesHtml = sales.map(currentSale => `
                <tr>
                    <td>${this.escapeHtml(currentSale.receipt_no || ('#' + currentSale.id))}</td>
                    <td>${this.escapeHtml(this.formatReportDate(currentSale.sold_at))}</td>
                    <td>${this.escapeHtml(currentSale.payment_method === 'cash' ? 'Nakit' : currentSale.payment_method === 'card' ? 'Kart' : currentSale.payment_method === 'credit' ? 'Veresiye' : 'Karışık')}</td>
                    <td style="text-align:right;">${this.escapeHtml(this.formatCurrency(currentSale.grand_total))}</td>
                </tr>
            `).join('');

            const transactionsHtml = includeAllHistory ? transactions.map(transaction => `
                <tr>
                    <td>${this.escapeHtml(transaction.description || transaction.type || 'Hareket')}</td>
                    <td>${this.escapeHtml(this.formatReportDate(transaction.transaction_date || transaction.created_at))}</td>
                    <td style="text-align:right;">${this.escapeHtml((transaction.amount >= 0 ? '+' : '-') + this.formatCurrency(Math.abs(transaction.amount || 0)))}</td>
                </tr>
            `).join('') : '';

            const html = `<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>${this.escapeHtml(reportTitle)}</title><style>
                body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;padding:24px;color:#111827;background:#fff}
                h1{font-size:24px;margin-bottom:8px} h2{font-size:16px;margin:24px 0 12px} p{margin:4px 0;color:#4b5563}
                .cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:20px 0}
                .card{border:1px solid #e5e7eb;border-radius:14px;padding:14px;background:#f9fafb}
                .card .label{font-size:12px;color:#6b7280;margin-bottom:6px}.card .value{font-size:20px;font-weight:800;color:#111827}
                table{width:100%;border-collapse:collapse;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden} th,td{padding:10px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;text-align:left}
                th{background:#f3f4f6;color:#374151;font-weight:700} tr:last-child td{border-bottom:none}
                @media print{body{padding:8mm}}
            </style></head><body>
                <h1>${this.escapeHtml(reportTitle)}</h1>
                <p>Müşteri: ${this.escapeHtml(customer.name)}</p>
                <p>Bakiye: ${this.escapeHtml(this.formatCurrency(customer.balance || 0))}</p>
                <p>Kredi Limiti: ${this.escapeHtml(this.formatCurrency(customer.credit_limit || 0))}</p>
                <div class="cards">
                    <div class="card"><div class="label">Fiş Sayısı</div><div class="value">${sales.length}</div></div>
                    <div class="card"><div class="label">Toplam Tutar</div><div class="value">${this.escapeHtml(this.formatCurrency(total))}</div></div>
                    <div class="card"><div class="label">Rapor Tipi</div><div class="value" style="font-size:16px;">${this.escapeHtml(scope === 'single' ? 'Tek Fiş' : (scope === 'all' ? 'Tüm Geçmiş' : 'Seçili Fişler'))}</div></div>
                </div>
                <h2>Fişler</h2>
                <table><thead><tr><th>Fiş No</th><th>Tarih</th><th>Ödeme</th><th style="text-align:right;">Tutar</th></tr></thead><tbody>${salesHtml}</tbody></table>
                ${includeAllHistory ? `<h2>Hesap Hareketleri</h2><table><thead><tr><th>Açıklama</th><th>Tarih</th><th style="text-align:right;">Tutar</th></tr></thead><tbody>${transactionsHtml || '<tr><td colspan="3">Hareket yok</td></tr>'}</tbody></table>` : ''}
            </body></html>`;

            return { title: reportTitle, subject: reportTitle, text: textLines.join('\n'), html };
        },

        sendCustomerReportWhatsApp(scope, sale = null) {
            const report = this.buildCustomerReport(scope, sale);
            if (!report) return;
            const phone = this.getCustomerReportPhone();
            if (!phone) {
                showToast('WhatsApp için müşteri telefon numarası bulunamadı.', 'warning');
                return;
            }
            window.open(`https://wa.me/${phone}?text=${encodeURIComponent(report.text)}`, '_blank');
        },

        sendCustomerReportEmail(scope, sale = null) {
            const report = this.buildCustomerReport(scope, sale);
            if (!report) return;
            const email = this.getCustomerReportEmail();
            if (!email) {
                showToast('E-posta göndermek için müşteri e-posta adresi bulunamadı.', 'warning');
                return;
            }
            window.location.href = `mailto:${encodeURIComponent(email)}?subject=${encodeURIComponent(report.subject)}&body=${encodeURIComponent(report.text)}`;
        },

        printCustomerReport(scope, sale = null) {
            const report = this.buildCustomerReport(scope, sale);
            if (!report) return;
            const reportWindow = window.open('', '', 'width=960,height=760');
            reportWindow.document.write(report.html);
            reportWindow.document.close();
            setTimeout(() => { reportWindow.focus(); reportWindow.print(); }, 500);
        },

        printInvoice(invoice, printAreaId) {
            const printArea = document.getElementById(printAreaId);
            if (!printArea) return;
            const printWindow = window.open('', '', 'width=850,height=700');
            printWindow.document.write(`<!DOCTYPE html><html lang="tr"><head>
                <meta charset="UTF-8"><title>Satış Fişi</title>
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 20px; color: #111; background: #fff; }
                    .space-y-5 > * + * { margin-top: 20px; }
                    .space-y-3 > * + * { margin-top: 12px; }
                    h4 { font-size: 14px; font-weight: 700; margin-bottom: 10px; color: #374151; }
                    .card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background: #fff; margin-bottom: 16px; }
                    .row { display: flex; justify-content: space-between; align-items: center; padding: 10px 8px; border-bottom: 1px solid #f3f4f6; }
                    .row:last-child { border-bottom: none; }
                    .total-row { border-top: 2px solid #e5e7eb; padding-top: 12px; margin-top: 8px; display: flex; justify-content: space-between; }
                    .badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }
                    .text-xs { font-size: 12px; color: #6b7280; }
                    .font-black { font-weight: 900; }
                    .text-3xl { font-size: 28px; }
                    .text-right { text-align: right; }
                    .qty-box { display: inline-flex; width: 28px; height: 28px; border-radius: 6px; background: #f3f4f6; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #374151; }
                    footer { text-align: center; margin-top: 30px; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px; }
                    @media print { body { padding: 5mm; } }
                </style>
            </head><body>${printArea.innerHTML}</body></html>`);
            printWindow.document.close();
            setTimeout(() => { printWindow.focus(); printWindow.print(); }, 600);
        },

        get detailTimeline() {
            if (!this.detailData) return [];
            const sales = (this.detailData.recent_sales || []).map(s => ({...s, _type:'sale', _key:'sale_'+s.id}));
            const txs   = (this.detailData.transactions || []).map(t => ({...t, _type:'tx',   _key:'tx_'+t.id}));
            const all   = [...sales, ...txs];
            all.sort((a, b) => {
                const dA = new Date(a.sold_at || a.transaction_date || a.created_at);
                const dB = new Date(b.sold_at || b.transaction_date || b.created_at);
                return dB - dA;
            });
            return all;
        },

        openCreate() {
            this.resetForm();
            this.showFormModal = true;
        },

        openEdit(customer) {
            this.editingId = customer.id;
            // phones: mevcut telefon listesi yoksa legacy phone'dan oluştur
            let phones = [];
            if (customer.phones && customer.phones.length > 0) {
                phones = customer.phones.map(p => ({phone: p.phone, type: p.type || 'mobile', is_primary: !!p.is_primary}));
            } else if (customer.phone) {
                phones = [{phone: customer.phone, type: 'mobile', is_primary: true}];
            } else {
                phones = [{phone: '', type: 'mobile', is_primary: true}];
            }
            this.form = {
                name: customer.name || '',
                customer_group_id: customer.customer_group_id ? String(customer.customer_group_id) : '',
                phones: phones,
                email: customer.email || '',
                tax_number: customer.tax_number || '',
                tax_office: customer.tax_office || '',
                address: customer.address || '',
                type: customer.type || 'individual',
                notes: customer.notes || '',
            };
            this.showFormModal = true;
        },

        closeFormModal() {
            this.showFormModal = false;
            setTimeout(() => this.resetForm(), 300);
        },

        openCollect(id, name, balance) {
            this.resetCollectForm();
            this.collectCustomerId = id;
            this.collectCustomerName = name;
            this.collectCustomerBalance = balance;
            this.showCollectModal = true;
        },

        async submitForm() {
            if (!this.form.name) {
                showToast('Müşteri adı zorunludur.', 'error');
                return;
            }
            this.saving = true;

            const url = this.editingId
                ? '{{ route("pos.customers.update", ":id") }}'.replace(':id', this.editingId)
                : '{{ route("pos.customers.store") }}';

            const method = this.editingId ? 'PUT' : 'POST';

            try {
                const response = await posAjax(url, this.form, method);
                showToast(response.message || (this.editingId ? 'Müşteri güncellendi.' : 'Müşteri oluşturuldu.'), 'success');
                this.closeFormModal();
                window.location.reload();
            } catch (error) {
                showToast(error.message || 'Bir hata oluştu.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async submitCollect() {
            if (!this.collectForm.amount || this.collectForm.amount <= 0) {
                showToast('Geçerli bir tutar girin.', 'error');
                return;
            }
            this.collecting = true;

            const url = '{{ route("pos.customers.payment", ":id") }}'.replace(':id', this.collectCustomerId);

            try {
                const response = await posAjax(url, this.collectForm, 'POST');
                showToast(response.message || 'Tahsilat başarıyla alındı.', 'success');
                this.showCollectModal = false;
                window.location.reload();
            } catch (error) {
                showToast(error.message || 'Tahsilat işlemi başarısız.', 'error');
            } finally {
                this.collecting = false;
            }
        },

        applySearch() {
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.groupFilter) params.set('group_id', this.groupFilter);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },
        editGroup(id, name) {
            this.editingGroupId = id;
            this.newGroupName = name;
            this.showGroupForm = true;
        },
        async saveGroup() {
            if (!this.newGroupName.trim()) return;
            try {
                if (this.editingGroupId) {
                    await posAjax(`/customer-groups/${this.editingGroupId}`, { name: this.newGroupName }, 'PUT');
                    showToast('Grup güncellendi', 'success');
                } else {
                    await posAjax('/customer-groups', { name: this.newGroupName }, 'POST');
                    showToast('Grup oluşturuldu', 'success');
                }
                window.location.reload();
            } catch(e) { showToast(e.message || 'Hata', 'error'); }
        },
        async deleteGroup(id) {
            if (!confirm('Bu grubu silmek istediğinize emin misiniz?')) return;
            try { await posAjax(`/customer-groups/${id}`, {}, 'DELETE'); showToast('Grup silindi', 'success'); window.location.reload(); }
            catch(e) { showToast(e.message || 'Silinemedi', 'error'); }
        },
    };
}
</script>
@endpush
