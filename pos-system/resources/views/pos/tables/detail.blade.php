@extends('pos.layouts.app')
@section('title', 'Masa ' . $table->table_no . ' - ' . $table->name)

@section('content')
<div x-data="tableDetail()" x-init="init()" class="flex-1 flex flex-col lg:flex-row overflow-hidden">
    
    {{-- Mobil Tab Bar --}}
    <div class="lg:hidden flex shrink-0 bg-white border-b border-gray-200 z-20">
        <button @click="mobileTab = 'menu'" class="flex-1 py-3 text-center text-sm font-semibold transition-colors"
                :class="mobileTab === 'menu' ? 'text-brand-600 bg-brand-50' : 'text-gray-500'">
            <i class="fas fa-th-large mr-1"></i> Menü
        </button>
        <button @click="mobileTab = 'orders'" class="flex-1 py-3 text-center text-sm font-semibold transition-colors relative"
                :class="mobileTab === 'orders' ? 'text-brand-600 bg-brand-50' : 'text-gray-500'">
            <i class="fas fa-receipt mr-1"></i> Siparişler
            <span x-show="pendingItems.length > 0" class="absolute top-1 right-1/4 bg-red-500 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center font-bold" x-text="pendingItems.length"></span>
        </button>
    </div>

    {{-- SOL: Sipariş Ekleme --}}
    <div class="flex-1 flex flex-col border-r border-gray-200"
         :class="{ 'hidden lg:flex': mobileTab !== 'menu' }">
        {{-- Masa Bilgileri --}}
        <div class="p-3 sm:p-4 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-3 sm:gap-4">
                <a href="{{ route('pos.tables') }}" class="text-gray-500 hover:text-gray-800">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-base sm:text-lg font-bold text-gray-900">Masa {{ $table->table_no }} - {{ $table->name }}</h1>
                    <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                        <span><i class="fas fa-users mr-1"></i>{{ $table->capacity }} Kişilik</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $table->status === 'occupied' ? 'bg-red-100 text-red-600' : 
                               ($table->status === 'reserved' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-emerald-600') }}">
                            {{ $table->status === 'occupied' ? 'Dolu' : ($table->status === 'reserved' ? 'Reserve' : 'Boş') }}
                        </span>
                        @if($session)
                        <span><i class="fas fa-clock mr-1"></i>{{ $session->opened_at->diffForHumans(null, true) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 ml-auto sm:ml-0">
                @if(!$session)
                <button @click="openTable()" class="px-3 sm:px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:shadow-lg hover:shadow-emerald-200 rounded-xl text-xs sm:text-sm font-medium text-white">
                    <i class="fas fa-door-open mr-1"></i> <span class="hidden sm:inline">Masa </span>Aç
                </button>
                @else
                <button @click="showTransfer = true" class="px-2.5 sm:px-3 py-2 bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl text-xs sm:text-sm text-white">
                    <i class="fas fa-exchange-alt mr-1"></i> Transfer
                </button>
                <button @click="showPayModal = true" class="px-3 sm:px-4 py-2 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-xl text-xs sm:text-sm font-medium text-white">
                    <i class="fas fa-cash-register mr-1"></i> Hesap Al
                </button>
                @endif
            </div>
        </div>

        @if($session)
        {{-- Ürün Arama --}}
        <div class="p-3 border-b border-gray-200">
            <div class="flex gap-2">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts()"
                           placeholder="Ürün arayın..."
                           class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-sm">
                </div>
            </div>
            {{-- Kategoriler --}}
            <div class="flex gap-1.5 mt-2 overflow-x-auto pb-1">
                <button @click="filterCategory(null)" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap"
                        :class="selectedCategory === null ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                    Tümü
                </button>
                @foreach($categories as $cat)
                <button @click="filterCategory({{ $cat->id }})" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap"
                        :class="selectedCategory === {{ $cat->id }} ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Ürün Grid --}}
        <div class="flex-1 overflow-y-auto p-2 sm:p-3">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-1.5 sm:gap-2">
                <template x-for="product in filteredProducts" :key="product.id">
                    <button @click="addToOrder(product)"
                            class="bg-white border border-gray-100 rounded-xl p-3 text-left hover:border-brand-300 transition-all">
                        <div class="text-sm font-medium text-gray-900 truncate" x-text="product.name"></div>
                        <div class="text-sm font-bold text-brand-500 mt-1" x-text="formatCurrency(product.sale_price)"></div>
                    </button>
                </template>
            </div>
        </div>
        @else
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center text-gray-500">
                <i class="fas fa-chair text-5xl mb-4"></i>
                <p class="text-lg">Masa henüz açılmamış</p>
                <p class="text-sm mt-1">Sipariş eklemek için masayı açın</p>
            </div>
        </div>
        @endif
    </div>

    {{-- SAĞ: Mevcut Siparişler --}}
    <div class="w-full lg:w-96 flex flex-col bg-gray-50"
         :class="{ 'hidden lg:flex': mobileTab !== 'orders' }">
        <div class="p-3 border-b border-gray-200">
            <h2 class="text-md font-bold text-gray-900">Siparişler</h2>
        </div>

        @if($session)
        {{-- Bekleyen Sipariş --}}
        <div x-show="pendingItems.length > 0" class="border-b border-gray-200">
            <div class="px-3 py-2 bg-amber-50 border-b border-amber-200">
                <span class="text-xs font-bold text-amber-600"><i class="fas fa-clock mr-1"></i> Yeni Sipariş</span>
            </div>
            <div class="divide-y divide-gray-100">
                <template x-for="(item, idx) in pendingItems" :key="idx">
                    <div class="px-3 py-2 flex items-center justify-between">
                        <div class="flex-1">
                            <span class="text-sm text-gray-900" x-text="item.product_name"></span>
                            <span class="text-xs text-gray-500 ml-1" x-text="'x' + item.quantity"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-900" x-text="formatCurrency(item.total)"></span>
                            <button @click="removePending(idx)" class="text-gray-500 hover:text-red-500">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="p-3">
                <div class="flex items-center justify-between mb-2 px-1">
                    <span class="text-sm font-semibold text-gray-700">Bekleyen Toplam</span>
                    <span class="text-sm font-bold text-amber-600" x-text="formatCurrency(pendingTotal)"></span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" x-model="orderNote" placeholder="Sipariş notu..."
                           class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <button @click="submitOrder()" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:shadow-lg hover:shadow-emerald-200 rounded-lg text-sm font-medium text-white">
                        <i class="fas fa-paper-plane mr-1"></i> Gönder
                    </button>
                </div>
            </div>
        </div>

        {{-- Mevcut Siparişler --}}
        <div class="flex-1 overflow-y-auto">
            @forelse($session->orders as $order)
            <div class="border-b border-gray-200">
                <div class="px-3 py-2 bg-gray-50 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        Sipariş #{{ $order->order_number }} - {{ $order->created_at->format('H:i') }}
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $order->status === 'completed' ? 'bg-green-100 text-emerald-600' : 
                           ($order->status === 'preparing' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-600') }}">
                        {{ $order->status === 'completed' ? 'Hazır' : ($order->status === 'preparing' ? 'Hazırlanıyor' : 'Bekliyor') }}
                    </span>
                </div>
                @foreach($order->items as $item)
                <div class="px-3 py-1.5 flex items-center justify-between text-sm">
                    <div>
                        <span class="text-gray-900">{{ $item->product_name }}</span>
                        <span class="text-gray-500 ml-1">x{{ $item->quantity }}</span>
                        @if($item->notes)
                        <div class="text-xs text-amber-600"><i class="fas fa-sticky-note mr-1"></i>{{ $item->notes }}</div>
                        @endif
                    </div>
                    <span class="text-gray-900">{{ number_format($item->total, 2) }} ₺</span>
                </div>
                @endforeach
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-utensils text-3xl mb-2"></i>
                <p class="text-sm">Henüz sipariş yok</p>
            </div>
            @endforelse
        </div>
        @else
        <div class="flex-1 flex items-center justify-center text-gray-500">
            <p class="text-sm">Masa kapalı</p>
        </div>
        @endif

        {{-- Alt Toplam --}}
        @if($session)
        <div class="border-t border-gray-200 p-4 bg-white">
            <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-800 font-semibold">Toplam Tutar</span>
                <span class="text-lg font-bold text-brand-600" x-text="formatCurrency(grandTotal)"></span>
            </div>
            <div x-show="pendingTotal > 0" class="flex items-center justify-between text-xs text-amber-600 mb-1">
                <span><i class="fas fa-clock mr-1"></i>Bekleyen</span>
                <span x-text="formatCurrency(pendingTotal)"></span>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>{{ $session->orders->count() }} sipariş</span>
                <span>{{ $session->orders->flatMap->items->count() }} kalem</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Ödeme Modal --}}
    <div x-show="showPayModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-6 pb-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-cash-register mr-2 text-brand-500"></i>Hesap Al</h3>
                <button @click="showPayModal = false" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>

            {{-- Sekme Başlıkları --}}
            <div class="flex border-b border-gray-100 px-6">
                <button @click="payTab = 'full'" :class="payTab === 'full' ? 'border-b-2 border-brand-500 text-brand-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="py-3 pr-6 text-sm transition-colors">
                    <i class="fas fa-receipt mr-1"></i>Tam Hesap
                </button>
                <button @click="payTab = 'split'" :class="payTab === 'split' ? 'border-b-2 border-brand-500 text-brand-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="py-3 px-4 text-sm transition-colors">
                    <i class="fas fa-cut mr-1"></i>Ürün Bazlı Ödeme
                </button>
            </div>

            <div class="overflow-y-auto flex-1 p-6">

                {{-- TAM HESAP SEKMESİ --}}
                <div x-show="payTab === 'full'">
                    <div class="bg-gray-50 rounded-xl p-4 mb-5 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Masa Tutarı</span>
                        <span class="text-xl font-bold text-gray-900">{{ number_format($session?->orders->sum(function($o) { return $o->items->where('status','!=','cancelled')->where('status','!=','paid')->sum('total'); }) ?? 0, 2) }} ₺</span>
                    </div>

                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Ödeme Yöntemi</p>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <button @click="payMethod = 'cash'" :class="payMethod === 'cash' ? 'ring-2 ring-brand-500 bg-brand-50 text-brand-700' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'"
                                class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium transition-all">
                            <i class="fas fa-money-bill-wave"></i> Nakit
                        </button>
                        <button @click="payMethod = 'card'" :class="payMethod === 'card' ? 'ring-2 ring-blue-500 bg-blue-50 text-blue-700' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'"
                                class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium transition-all">
                            <i class="fas fa-credit-card"></i> Kart
                        </button>
                        <button @click="payMethod = 'credit'; searchPayCustomers('')" :class="payMethod === 'credit' ? 'ring-2 ring-amber-500 bg-amber-50 text-amber-700' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'"
                                class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium transition-all">
                            <i class="fas fa-user-clock"></i> Veresiye
                        </button>
                        <button @click="payMethod = 'mixed'" :class="payMethod === 'mixed' ? 'ring-2 ring-purple-500 bg-purple-50 text-purple-700' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'"
                                class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium transition-all">
                            <i class="fas fa-layer-group"></i> Karışık
                        </button>
                    </div>

                    <div x-show="payMethod === 'credit' || payMethod === 'mixed'" class="mb-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Cari Seçin</p>
                        <div class="relative">
                            <input type="text" x-model="payCustomerSearch" @input.debounce.300ms="searchPayCustomers(payCustomerSearch)"
                                   @focus="searchPayCustomers(payCustomerSearch)"
                                   placeholder="Müşteri adı veya telefon..." 
                                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                            <div x-show="payCustomers.length > 0 && !payCustomerId" class="absolute z-10 left-0 right-0 mt-1 bg-white rounded-xl border border-gray-200 shadow-lg max-h-40 overflow-y-auto">
                                <template x-for="c in payCustomers" :key="c.id">
                                    <button @click="payCustomerId = c.id; payCustomerName = c.name; payCustomerSearch = c.name; payCustomers = []"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-50 last:border-0">
                                        <span class="font-medium text-gray-900" x-text="c.name"></span>
                                        <span class="text-xs text-gray-400 ml-2" x-text="c.phone || ''"></span>
                                        <span x-show="c.balance" class="text-xs ml-1" :class="c.balance < 0 ? 'text-red-500' : 'text-emerald-600'" x-text="(c.balance > 0 ? '+' : '') + parseFloat(c.balance).toFixed(2) + '₺'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-show="payCustomerId" class="mt-2 flex items-center gap-2 bg-brand-50 border border-brand-200 rounded-xl px-3 py-2">
                            <i class="fas fa-user-check text-brand-500 text-xs"></i>
                            <span class="text-sm text-brand-700 font-medium" x-text="payCustomerName"></span>
                            <button @click="payCustomerId = null; payCustomerName = ''; payCustomerSearch = ''" class="ml-auto text-brand-400 hover:text-red-500">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div x-show="payMethod === 'mixed'" class="mb-4 space-y-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Tutar Dağılımı</p>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 w-20"><i class="fas fa-money-bill-wave mr-1 text-emerald-500"></i>Nakit</span>
                            <input type="number" x-model.number="payMixedCash" min="0" step="0.01" placeholder="0.00"
                                   class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none">
                            <span class="text-xs text-gray-400">₺</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 w-20"><i class="fas fa-credit-card mr-1 text-blue-500"></i>Kart</span>
                            <input type="number" x-model.number="payMixedCard" min="0" step="0.01" placeholder="0.00"
                                   class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none">
                            <span class="text-xs text-gray-400">₺</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 w-20"><i class="fas fa-user-clock mr-1 text-amber-500"></i>Veresiye</span>
                            <input type="number" x-model.number="payMixedCredit" min="0" step="0.01" placeholder="0.00"
                                   class="flex-1 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none">
                            <span class="text-xs text-gray-400">₺</span>
                        </div>
                    </div>

                    <button @click="confirmPayTable()" :disabled="payProcessing || (payMethod==='credit' && !payCustomerId) || (payMethod==='mixed' && !payCustomerId && payMixedCredit > 0)"
                            class="w-full py-3 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-xl font-medium text-white disabled:opacity-50 flex items-center justify-center gap-2">
                        <template x-if="payProcessing"><i class="fas fa-spinner fa-spin"></i></template>
                        <template x-if="!payProcessing"><i class="fas fa-check-circle"></i></template>
                        <span x-text="payProcessing ? 'İşleniyor...' : 'Ödemeyi Tamamla'"></span>
                    </button>
                </div>

                {{-- ÜRÜN BAZLI ÖDEME SEKMESİ --}}
                <div x-show="payTab === 'split'">
                    <p class="text-xs text-gray-500 mb-3">Ödemesini almak istediğiniz kalemleri seçin:</p>

                    {{-- Kalem Listesi --}}
                    <div class="space-y-1 mb-4 max-h-52 overflow-y-auto border border-gray-100 rounded-xl divide-y divide-gray-50">
                        @forelse($session?->orders ?? [] as $order)
                            @foreach($order->items as $item)
                                @if(!in_array($item->status, ['cancelled', 'paid']))
                                <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 cursor-pointer"
                                       :class="splitSelectedIds.includes({{ $item->id }}) ? 'bg-brand-50' : ''">
                                    <input type="checkbox" :value="{{ $item->id }}"
                                           @change="toggleSplitItem({{ $item->id }}, {{ $item->total }})"
                                           :checked="splitSelectedIds.includes({{ $item->id }})"
                                           class="w-4 h-4 rounded accent-brand-500">
                                    <span class="flex-1 text-sm text-gray-800">{{ $item->product_name }}
                                        <span class="text-xs text-gray-400 ml-1">x{{ $item->quantity }}</span>
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">{{ number_format($item->total, 2) }} ₺</span>
                                </label>
                                @endif
                            @endforeach
                        @empty
                            <div class="px-4 py-6 text-center text-gray-400 text-sm">Ödenecek kalem yok</div>
                        @endforelse
                    </div>

                    {{-- Seçili Toplam --}}
                    <div class="flex items-center justify-between bg-brand-50 border border-brand-200 rounded-xl px-4 py-3 mb-4">
                        <div>
                            <span class="text-xs text-brand-600">Seçili Kalem Toplamı</span>
                            <div class="text-lg font-bold text-brand-700" x-text="formatCurrency(splitTotal)"></div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-500" x-text="splitSelectedIds.length + ' kalem'"></span>
                        </div>
                    </div>

                    {{-- Ödeme Yöntemi --}}
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Ödeme Yöntemi</p>
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <button @click="splitMethod = 'cash'" :class="splitMethod === 'cash' ? 'ring-2 ring-emerald-500 bg-emerald-50 text-emerald-700' : 'bg-white border border-gray-200 text-gray-700'"
                                class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all">
                            <i class="fas fa-money-bill-wave"></i> Nakit
                        </button>
                        <button @click="splitMethod = 'card'" :class="splitMethod === 'card' ? 'ring-2 ring-blue-500 bg-blue-50 text-blue-700' : 'bg-white border border-gray-200 text-gray-700'"
                                class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all">
                            <i class="fas fa-credit-card"></i> Kart
                        </button>
                        <button @click="splitMethod = 'credit'; searchPayCustomers('')" :class="splitMethod === 'credit' ? 'ring-2 ring-amber-500 bg-amber-50 text-amber-700' : 'bg-white border border-gray-200 text-gray-700'"
                                class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all">
                            <i class="fas fa-user-clock"></i> Veresiye
                        </button>
                    </div>

                    {{-- Veresiye müşteri --}}
                    <div x-show="splitMethod === 'credit'" x-transition class="mb-4">
                        <div class="relative">
                            <input type="text" x-model="payCustomerSearch" @input.debounce.300ms="searchPayCustomers(payCustomerSearch)"
                                   @focus="searchPayCustomers(payCustomerSearch)"
                                   placeholder="Müşteri adı veya telefon..." 
                                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                            <div x-show="payCustomers.length > 0 && !payCustomerId" class="absolute z-10 left-0 right-0 mt-1 bg-white rounded-xl border border-gray-200 shadow-lg max-h-32 overflow-y-auto">
                                <template x-for="c in payCustomers" :key="c.id">
                                    <button @click="payCustomerId = c.id; payCustomerName = c.name; payCustomerSearch = c.name; payCustomers = []"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm">
                                        <span class="font-medium text-gray-900" x-text="c.name"></span>
                                        <span class="text-xs text-gray-400 ml-2" x-text="c.phone || ''"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-show="payCustomerId" class="mt-2 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                            <i class="fas fa-user-check text-amber-500 text-xs"></i>
                            <span class="text-sm text-amber-700 font-medium" x-text="payCustomerName"></span>
                            <button @click="payCustomerId = null; payCustomerName = ''; payCustomerSearch = ''" class="ml-auto text-amber-400 hover:text-red-500">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <button @click="confirmSplitPay()"
                            :disabled="splitSelectedIds.length === 0 || payProcessing || (splitMethod === 'credit' && !payCustomerId)"
                            class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:shadow-lg hover:shadow-emerald-200 rounded-xl font-medium text-white disabled:opacity-50 flex items-center justify-center gap-2">
                        <template x-if="payProcessing"><i class="fas fa-spinner fa-spin"></i></template>
                        <template x-if="!payProcessing"><i class="fas fa-check-circle"></i></template>
                        <span x-text="payProcessing ? 'İşleniyor...' : ('Seçili Kalemleri Öde (' + formatCurrency(splitTotal) + ')')"></span>
                    </button>
                    <p x-show="splitMethod === 'credit' && !payCustomerId" class="text-xs text-red-500 text-center mt-2">Veresiye için müşteri seçimi zorunludur.</p>
                </div>

            </div>
        </div>
    </div>

    {{-- Transfer Modal --}}
    <div x-show="showTransfer" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6 w-full max-w-sm mx-4 shadow-2xl" @click.away="showTransfer = false">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Masa Transfer</h3>
            <p class="text-sm text-gray-500 mb-3">Masa {{ $table->table_no }} siparişlerini hangi masaya taşımak istiyorsunuz?</p>
            <select x-model="transferTarget" 
                    class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-sm">
                <option value="">Masa seçin...</option>
                @foreach($emptyTables as $emptyTable)
                <option value="{{ $emptyTable->id }}">Masa {{ $emptyTable->table_no }} - {{ $emptyTable->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2 mt-4">
                <button @click="showTransfer = false" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm text-gray-700">İptal</button>
                <button @click="transferTable()" :disabled="!transferTarget" class="flex-1 py-2 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 disabled:opacity-50 rounded-lg text-sm font-medium text-white">Transfer Et</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function tableDetail() {
    return {
        mobileTab: 'menu',
        products: [],
        filteredProducts: [],
        searchQuery: '',
        selectedCategory: null,
        pendingItems: [],
        orderNote: '',
        showTransfer: false,
        transferTarget: '',

        // Ödeme modal
        showPayModal: false,
        payTab: 'full',
        payMethod: 'cash',
        payCustomerSearch: '',
        payCustomers: [],
        payCustomerId: null,
        payCustomerName: '',
        payMixedCash: 0,
        payMixedCard: 0,
        payMixedCredit: 0,
        payProcessing: false,
        // Split (ürün bazlı) ödeme
        splitSelectedIds: [],
        splitTotal: 0,
        splitMethod: 'cash',
        splitItemTotals: @json($splitItemTotals),
        existingTotal: {{ $session ? $session->orders->sum(function($o) { return $o->items->sum('total'); }) : 0 }},

        get pendingTotal() {
            return this.pendingItems.reduce((sum, item) => sum + item.total, 0);
        },

        get grandTotal() {
            return this.existingTotal + this.pendingTotal;
        },

        async init() {
            await this.loadProducts();
        },

        async openTable() {
            try {
                const data = await posAjax('{{ route("pos.tables.open", $table->id) }}', {}, 'POST');
                if (data.success) {
                    showToast('Masa açıldı!', 'success');
                    setTimeout(() => location.reload(), 600);
                } else {
                    showToast(data.message || 'Masa açılamadı.', 'error');
                }
            } catch(e) {
                showToast(e.message || 'Masa açılamadı.', 'error');
            }
        },

        async loadProducts() {
            try {
                const data = await posAjax('{{ route("pos.products.search") }}', {}, 'GET');
                this.products = data;
                this.filteredProducts = data;
            } catch(e) { console.error(e); }
        },

        async searchProducts() {
            if (!this.searchQuery.trim()) { this.applyFilter(); return; }
            try {
                const data = await posAjax('{{ route("pos.products.search") }}?q=' + encodeURIComponent(this.searchQuery), {}, 'GET');
                this.products = data;
                this.filteredProducts = data;
            } catch(e) { console.error(e); }
        },

        filterCategory(catId) {
            this.selectedCategory = catId;
            this.applyFilter();
        },

        applyFilter() {
            if (this.selectedCategory) {
                this.filteredProducts = this.products.filter(p => p.category_id === this.selectedCategory);
            } else {
                this.filteredProducts = [...this.products];
            }
        },

        addToOrder(product) {
            const existing = this.pendingItems.find(i => i.product_id === product.id);
            if (existing) {
                existing.quantity++;
                existing.total = existing.quantity * existing.unit_price;
            } else {
                this.pendingItems.push({
                    product_id: product.id,
                    product_name: product.name,
                    quantity: 1,
                    unit_price: product.sale_price,
                    total: product.sale_price,
                });
            }
            if (window.innerWidth < 1024) this.mobileTab = 'orders';
        },

        removePending(index) {
            this.pendingItems.splice(index, 1);
        },

        async submitOrder() {
            if (this.pendingItems.length === 0) return;
            try {
                const data = await posAjax('{{ route("pos.tables.order", $table->id) }}', {
                    items: this.pendingItems,
                    notes: this.orderNote,
                }, 'POST');
                if (data.success) {
                    showToast('Sipariş mutfağa gönderildi!');
                    this.pendingItems = [];
                    this.orderNote = '';
                    location.reload();
                } else {
                    showToast(data.message || 'Sipariş gönderilemedi.', 'error');
                }
            } catch(e) {
                showToast(e.message || 'Sipariş gönderilemedi.', 'error');
            }
        },

        async searchPayCustomers(query) {
            try {
                const url = query && query.length > 0
                    ? '{{ route("pos.customers.search") }}?q=' + encodeURIComponent(query)
                    : '{{ route("pos.customers.search") }}';
                const data = await posAjax(url, {}, 'GET');
                this.payCustomers = data;
            } catch(e) { console.error(e); }
        },

        async confirmPayTable() {
            if (this.payProcessing) return;
            if ((this.payMethod === 'credit') && !this.payCustomerId) {
                showToast('Veresiye için müşteri seçiniz.', 'error'); return;
            }
            if (this.payMethod === 'mixed' && this.payMixedCredit > 0 && !this.payCustomerId) {
                showToast('Veresiye tutarı için müşteri seçiniz.', 'error'); return;
            }
            this.payProcessing = true;
            try {
                const payload = { payment_method: this.payMethod };
                if (this.payCustomerId) payload.customer_id = this.payCustomerId;
                if (this.payMethod === 'cash') {
                    payload.cash_amount = 0; // backend zaten grand_total'ı alır
                } else if (this.payMethod === 'card') {
                    payload.card_amount = 0;
                } else if (this.payMethod === 'mixed') {
                    payload.cash_amount  = this.payMixedCash;
                    payload.card_amount  = this.payMixedCard;
                    payload.credit_amount = this.payMixedCredit;
                }
                const data = await posAjax('{{ route("pos.tables.pay", $table->id) }}', payload, 'POST');
                if (data.success) {
                    showToast('Hesap alındı! Satış: ' + (data.receipt_no || data.sale?.receipt_no || ''));
                    window.location.href = '{{ route("pos.tables") }}';
                } else {
                    showToast(data.message || 'Hesap alınamadı.', 'error');
                }
            } catch(e) {
                showToast(e.message || 'Hesap alınamadı.', 'error');
            } finally {
                this.payProcessing = false;
            }
        },

        async payTable() {
            this.showPayModal = true;
        },

        toggleSplitItem(id, total) {
            const idx = this.splitSelectedIds.indexOf(id);
            if (idx === -1) {
                this.splitSelectedIds.push(id);
            } else {
                this.splitSelectedIds.splice(idx, 1);
            }
            this.splitTotal = Math.round(
                this.splitSelectedIds.reduce((s, i) => s + (parseFloat(this.splitItemTotals[i]) || 0), 0) * 100
            ) / 100;
        },

        async confirmSplitPay() {
            if (this.splitSelectedIds.length === 0) { showToast('Kalem seçiniz.', 'error'); return; }
            if (this.splitMethod === 'credit' && !this.payCustomerId) { showToast('Veresiye için müşteri seçiniz.', 'error'); return; }
            if (this.payProcessing) return;
            this.payProcessing = true;
            try {
                const payload = {
                    item_ids: this.splitSelectedIds,
                    payment_method: this.splitMethod,
                    customer_id: this.payCustomerId,
                    cash_amount:  this.splitMethod === 'cash'   ? this.splitTotal : 0,
                    card_amount:  this.splitMethod === 'card'   ? this.splitTotal : 0,
                    credit_amount: this.splitMethod === 'credit' ? this.splitTotal : 0,
                };
                const data = await posAjax('{{ route("pos.tables.pay.partial", $table->id) }}', payload, 'POST');
                if (data.success) {
                    showToast(data.message || 'Ödeme alındı.');
                    if (data.table_closed) {
                        window.location.href = '{{ route("pos.tables") }}';
                    } else {
                        location.reload();
                    }
                } else {
                    showToast(data.message || 'Ödeme alınamadı.', 'error');
                }
            } catch(e) {
                showToast(e.message || 'Hata oluştu.', 'error');
            } finally {
                this.payProcessing = false;
            }
        },

        async transferTable() {
            if (!this.transferTarget) return;
            try {
                const data = await posAjax('{{ route("pos.tables.transfer", $table->id) }}', {
                    target_table_id: this.transferTarget,
                }, 'POST');
                if (data.success) {
                    showToast('Masa transfer edildi!');
                    window.location.href = '{{ route("pos.tables") }}';
                } else {
                    showToast(data.message || 'Transfer başarısız.', 'error');
                }
            } catch(e) {
                showToast(e.message || 'Transfer başarısız.', 'error');
            }
        },
    };
}
</script>
@endpush
