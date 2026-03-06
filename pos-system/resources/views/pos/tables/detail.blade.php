@extends('pos.layouts.app')
@section('title', 'Masa ' . $table->table_no . ' - ' . $table->name)

@section('content')
<div x-data="tableDetail()" x-init="init()" class="flex-1 flex overflow-hidden">
    
    {{-- SOL: Sipariş Ekleme --}}
    <div class="flex-1 flex flex-col border-r border-gray-700">
        {{-- Masa Bilgileri --}}
        <div class="p-4 bg-gray-50 border-b border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('pos.tables') }}" class="text-gray-500 hover:text-gray-800">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Masa {{ $table->table_no }} - {{ $table->name }}</h1>
                    <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                        <span><i class="fas fa-users mr-1"></i>{{ $table->capacity }} Kişilik</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $table->status === 'occupied' ? 'bg-red-900/50 text-red-500' : 
                               ($table->status === 'reserved' ? 'bg-amber-900/50 text-amber-600' : 'bg-green-900/50 text-emerald-500') }}">
                            {{ $table->status === 'occupied' ? 'Dolu' : ($table->status === 'reserved' ? 'Reserve' : 'Boş') }}
                        </span>
                        @if($session)
                        <span><i class="fas fa-clock mr-1"></i>{{ $session->opened_at->diffForHumans(null, true) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if(!$session)
                <button @click="openTable()" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:shadow-lg hover:shadow-emerald-200 rounded-xl text-sm font-medium">
                    <i class="fas fa-door-open mr-1"></i> Masa Aç
                </button>
                @else
                <button @click="showTransfer = true" class="px-3 py-2 bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl text-sm">
                    <i class="fas fa-exchange-alt mr-1"></i> Transfer
                </button>
                <button @click="payTable()" class="px-4 py-2 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 rounded-xl text-sm font-medium">
                    <i class="fas fa-cash-register mr-1"></i> Hesap Al
                </button>
                @endif
            </div>
        </div>

        @if($session)
        {{-- Ürün Arama --}}
        <div class="p-3 border-b border-gray-700">
            <div class="flex gap-2">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-500"></i>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts()"
                           placeholder="Ürün arayın..."
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 text-sm">
                </div>
            </div>
            {{-- Kategoriler --}}
            <div class="flex gap-1.5 mt-2 overflow-x-auto pb-1">
                <button @click="filterCategory(null)" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap"
                        :class="selectedCategory === null ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900' : 'bg-slate-700 text-gray-500'">
                    Tümü
                </button>
                @foreach($categories as $cat)
                <button @click="filterCategory({{ $cat->id }})" 
                        class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap"
                        :class="selectedCategory === {{ $cat->id }} ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900' : 'bg-slate-700 text-gray-500'">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Ürün Grid --}}
        <div class="flex-1 overflow-y-auto p-3">
            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
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
    <div class="w-96 flex flex-col bg-gray-50">
        <div class="p-3 border-b border-gray-700">
            <h2 class="text-md font-bold text-gray-900">Siparişler</h2>
        </div>

        @if($session)
        {{-- Bekleyen Sipariş --}}
        <div x-show="pendingItems.length > 0" class="border-b border-gray-700">
            <div class="px-3 py-2 bg-amber-900/20 border-b border-amber-600/20">
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
                <div class="flex items-center gap-2">
                    <input type="text" x-model="orderNote" placeholder="Sipariş notu..."
                           class="flex-1 px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm text-gray-900 focus:outline-none">
                    <button @click="submitOrder()" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:shadow-lg hover:shadow-emerald-200 rounded-lg text-sm font-medium">
                        <i class="fas fa-paper-plane mr-1"></i> Gönder
                    </button>
                </div>
            </div>
        </div>

        {{-- Mevcut Siparişler --}}
        <div class="flex-1 overflow-y-auto">
            @forelse($session->orders as $order)
            <div class="border-b border-gray-700">
                <div class="px-3 py-2 bg-slate-800/50 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        Sipariş #{{ $order->order_number }} - {{ $order->created_at->format('H:i') }}
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $order->status === 'completed' ? 'bg-green-900/50 text-emerald-500' : 
                           ($order->status === 'preparing' ? 'bg-amber-900/50 text-amber-600' : 'bg-blue-900/50 text-brand-600') }}">
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
        <div class="border-t border-gray-700 p-4 bg-slate-800/50">
            <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-500">Sipariş Toplamı</span>
                <span class="text-gray-900 font-bold" id="session-total">
                    {{ number_format($session->orders->sum(function($o) { return $o->items->sum('total'); }), 2) }} ₺
                </span>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>{{ $session->orders->count() }} sipariş</span>
                <span>{{ $session->orders->flatMap->items->count() }} kalem</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Transfer Modal --}}
    <div x-show="showTransfer" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" x-cloak>
        <div class="bg-slate-800 rounded-2xl border border-gray-700 p-6 w-96 shadow-2xl" @click.away="showTransfer = false">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Masa Transfer</h3>
            <p class="text-sm text-gray-500 mb-3">Masa {{ $table->table_no }} siparişlerini hangi masaya taşımak istiyorsunuz?</p>
            <select x-model="transferTarget" 
                    class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-gray-900 focus:outline-none text-sm">
                <option value="">Masa seçin...</option>
                @foreach($emptyTables as $emptyTable)
                <option value="{{ $emptyTable->id }}">Masa {{ $emptyTable->table_no }} - {{ $emptyTable->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2 mt-4">
                <button @click="showTransfer = false" class="flex-1 py-2 bg-slate-600 hover:bg-gray-200 rounded-lg text-sm">İptal</button>
                <button @click="transferTable()" :disabled="!transferTarget" class="flex-1 py-2 bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 disabled:opacity-50 rounded-lg text-sm font-medium">Transfer Et</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function tableDetail() {
    return {
        products: [],
        filteredProducts: [],
        searchQuery: '',
        selectedCategory: null,
        pendingItems: [],
        orderNote: '',
        showTransfer: false,
        transferTarget: '',

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

        async payTable() {
            try {
                const data = await posAjax('{{ route("pos.tables.pay", $table->id) }}', {
                    payment_method: 'cash',
                }, 'POST');
                if (data.success) {
                    showToast('Hesap alındı! Satış: ' + (data.receipt_no || ''));
                    window.location.href = '{{ route("pos.tables") }}';
                } else {
                    showToast(data.message || 'Hesap alınamadı.', 'error');
                }
            } catch(e) {
                showToast(e.message || 'Hesap alınamadı.', 'error');
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
