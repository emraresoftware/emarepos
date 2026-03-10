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
                        <th class="px-4 py-3.5 text-right">Bakiye</th>
                        <th class="px-4 py-3.5 text-right hidden md:table-cell">Toplam Satış</th>
                        <th class="px-4 py-3.5 hidden lg:table-cell">Son İşlem</th>
                        <th class="px-4 py-3.5 text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50 transition-colors">
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
                                <span class="text-gray-700">{{ $customer->phone ?? '-' }}</span>
                            </td>
                            {{-- Email --}}
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-gray-500">{{ $customer->email ?? '-' }}</span>
                            </td>
                            {{-- Tax Number --}}
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span class="font-mono text-xs text-gray-500">{{ $customer->tax_number ?? '-' }}</span>
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
                                    <button @click="openDetail({{ $customer->id }})"
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
                                    {{-- Collect Payment --}}
                                    <button @click="openCollect({{ $customer->id }}, '{{ addslashes($customer->name) }}', {{ $customer->balance ?? 0 }})"
                                            class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-colors"
                                            title="Tahsilat Al">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
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
        <div x-show="showDetailModal"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">

            {{-- Header --}}
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div x-show="!detailLoading && detailData">
                    <h2 class="text-lg font-bold text-gray-900" x-text="detailData?.customer?.name"></h2>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="(detailData?.customer?.phone || '') + (detailData?.customer?.email ? ' • ' + detailData.customer.email : '')"></p>
                </div>
                <div x-show="detailLoading" class="text-sm text-gray-400"><i class="fas fa-spinner fa-spin mr-1"></i>Yükleniyor...</div>
                <button @click="showDetailModal = false" class="p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-50 rounded-xl ml-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div x-show="!detailLoading && detailData" class="flex flex-col overflow-hidden flex-1">
                {{-- Özet Bakiye Kartları --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 border-b border-gray-100 flex-shrink-0">
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-gray-400">Bakiye</p>
                        <p class="font-bold text-lg mt-0.5" :class="(detailData?.customer?.balance??0)<0?'text-red-500':((detailData?.customer?.balance??0)>0?'text-emerald-600':'text-gray-700')" x-text="formatCurrency(detailData?.customer?.balance??0)"></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-gray-400">Toplam Alışveriş</p>
                        <p class="font-bold text-lg mt-0.5 text-gray-900" x-text="detailData?.recent_sales?.length + ' satış'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-gray-400">Toplam Hareket</p>
                        <p class="font-bold text-lg mt-0.5 text-gray-900" x-text="detailData?.transactions?.length + ' kayıt'"></p>
                    </div>
                </div>

                {{-- Sekmeler --}}
                <div class="flex gap-1 px-4 pt-3 flex-shrink-0">
                    <button @click="detailTab='sales'" :class="detailTab==='sales'?'bg-brand-50 text-brand-700 border-brand-300':'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'"
                            class="px-4 py-2 rounded-xl text-sm font-medium border transition-colors">
                        <i class="fas fa-shopping-cart mr-1"></i> Satışlar (<span x-text="detailData?.recent_sales?.length"></span>)
                    </button>
                    <button @click="detailTab='transactions'" :class="detailTab==='transactions'?'bg-brand-50 text-brand-700 border-brand-300':'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'"
                            class="px-4 py-2 rounded-xl text-sm font-medium border transition-colors">
                        <i class="fas fa-exchange-alt mr-1"></i> Hesap Hareketleri (<span x-text="detailData?.transactions?.length"></span>)
                    </button>
                </div>

                {{-- İçerik --}}
                <div class="flex-1 overflow-y-auto px-4 pb-4 pt-3">

                    {{-- Satışlar --}}
                    <div x-show="detailTab==='sales'">
                        <template x-if="detailData?.recent_sales?.length === 0">
                            <div class="text-center py-12 text-gray-400"><i class="fas fa-shopping-cart text-3xl mb-2"></i><p class="text-sm">Henüz satış yok</p></div>
                        </template>
                        <template x-for="sale in (detailData?.recent_sales || [])" :key="sale.id">
                            <div class="flex items-center justify-between py-3 border-b border-gray-50 hover:bg-gray-50 rounded-xl px-2 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs"
                                         :class="sale.payment_method==='cash'?'bg-emerald-50 text-emerald-600':sale.payment_method==='card'?'bg-blue-50 text-blue-600':sale.payment_method==='credit'?'bg-amber-50 text-amber-600':'bg-purple-50 text-purple-600'">
                                        <i :class="sale.payment_method==='cash'?'fas fa-money-bill-wave':sale.payment_method==='card'?'fas fa-credit-card':sale.payment_method==='credit'?'fas fa-user-clock':'fas fa-layer-group'"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900" x-text="'#' + (sale.receipt_no || sale.id)"></p>
                                        <p class="text-xs text-gray-400" x-text="sale.sold_at ? new Date(sale.sold_at).toLocaleDateString('tr-TR', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-'"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900" x-text="formatCurrency(sale.grand_total)"></p>
                                    <p class="text-xs text-gray-400" x-text="sale.payment_method==='cash'?'Nakit':sale.payment_method==='card'?'Kart':sale.payment_method==='credit'?'Veresiye':'Karışık'"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Hesap Hareketleri --}}
                    <div x-show="detailTab==='transactions'">
                        <template x-if="detailData?.transactions?.length === 0">
                            <div class="text-center py-12 text-gray-400"><i class="fas fa-exchange-alt text-3xl mb-2"></i><p class="text-sm">Henüz hareket yok</p></div>
                        </template>
                        <template x-for="tx in (detailData?.transactions || [])" :key="tx.id">
                            <div class="flex items-center justify-between py-3 border-b border-gray-50 hover:bg-gray-50 rounded-xl px-2 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs"
                                         :class="tx.amount>=0?'bg-emerald-50 text-emerald-600':'bg-red-50 text-red-500'">
                                        <i :class="tx.amount>=0?'fas fa-arrow-down':'fas fa-arrow-up'"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900" x-text="tx.description || (tx.type==='payment'?'Tahsilat':tx.type==='sale'?'Veresiye Alışveriş':'İşlem')"></p>
                                        <p class="text-xs text-gray-400" x-text="tx.transaction_date ? new Date(tx.transaction_date).toLocaleDateString('tr-TR',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : new Date(tx.created_at).toLocaleDateString('tr-TR',{day:'2-digit',month:'short',year:'numeric'})"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold" :class="tx.amount>=0?'text-emerald-600':'text-red-500'" x-text="(tx.amount>=0?'+':'') + formatCurrency(tx.amount)"></p>
                                    <p class="text-xs text-gray-400" x-text="'Bakiye: ' + formatCurrency(tx.balance_after)"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
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

                {{-- Phone & Email --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefon</label>
                        <input type="tel" x-model="form.phone"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="0(5XX) XXX XX XX">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta</label>
                        <input type="email" x-model="form.email"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400"
                               placeholder="ornek@email.com">
                    </div>
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
                <h2 class="text-lg font-semibold text-gray-900">Tahsilat Al</h2>
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
        showDetailModal: false,
        showGroupPanel: false,
        showGroupForm: false,
        detailLoading: false,
        detailData: null,
        detailTab: 'sales',
        editingId: null,
        saving: false,
        collecting: false,
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        groupFilter: new URLSearchParams(window.location.search).get('group_id') || '',
        newGroupName: '',
        editingGroupId: null,

        collectCustomerId: null,
        collectCustomerName: '',
        collectCustomerBalance: 0,

        form: {
            name: '',
            customer_group_id: '',
            phone: '',
            email: '',
            tax_number: '',
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

        resetForm() {
            this.form = {
                name: '',
                customer_group_id: '',
                phone: '',
                email: '',
                tax_number: '',
                tax_office: '',
                address: '',
                type: 'individual',
                notes: '',
            };
            this.editingId = null;
        },

        resetCollectForm() {
            this.collectForm = {
                amount: '',
                payment_method: 'cash',
                description: '',
            };
            this.collectCustomerId = null;
            this.collectCustomerName = '';
            this.collectCustomerBalance = 0;
        },

        async openDetail(id) {
            this.detailData = null;
            this.detailLoading = true;
            this.detailTab = 'sales';
            this.showDetailModal = true;
            try {
                const data = await posAjax('/customers/' + id, {}, 'GET');
                this.detailData = data;
            } catch(e) {
                showToast('Detay yüklenemedi.', 'error');
                this.showDetailModal = false;
            } finally {
                this.detailLoading = false;
            }
        },

        openCreate() {
            this.resetForm();
            this.showFormModal = true;
        },

        openEdit(customer) {
            this.editingId = customer.id;
            this.form = {
                name: customer.name || '',
                customer_group_id: customer.customer_group_id ? String(customer.customer_group_id) : '',
                phone: customer.phone || '',
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
