@extends('pos.layouts.app')

@section('title', 'Depo / Stok Yönetimi')

@section('content')
<div class="p-3 sm:p-6 overflow-y-auto h-full" x-data="stockManager()" x-cloak>
    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Toplam Ürün</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_products'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Kritik Stok</p>
            <p class="text-2xl font-bold text-red-500 mt-1">{{ $stats['low_stock'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Stok Değeri</p>
            <p class="text-2xl font-bold text-emerald-400 mt-1">{{ formatCurrency($stats['total_stock_value']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Bugün Hareket</p>
            <p class="text-2xl font-bold text-brand-500 mt-1">{{ $stats['movements_today'] }}</p>
        </div>
    </div>

    {{-- Critical Stock Alert --}}
    @if($criticalStock->count() > 0)
        <div class="bg-red-500/5 border border-red-500/20 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <h3 class="text-red-500 font-semibold text-sm">Kritik Stok Uyarısı ({{ $criticalStock->count() }} ürün)</h3>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($criticalStock as $product)
                    <span class="text-xs bg-red-500/10 text-red-500 px-3 py-1.5 rounded-lg border border-red-500/20">
                        {{ $product->name }}: <strong>{{ $product->stock_quantity }}</strong> / {{ $product->critical_stock }} {{ $product->unit }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Header & Filters --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Stok Hareketleri</h1>
        <div class="flex flex-wrap items-center gap-3">
            <select x-model="filterType" @change="applyFilters()"
                    class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-3 py-2">
                <option value="">Tüm Tipler</option>
                <option value="purchase">Alış</option>
                <option value="sale">Satış</option>
                <option value="return">İade</option>
                <option value="adjustment">Düzeltme</option>
                <option value="transfer">Transfer</option>
                <option value="waste">Fire</option>
            </select>
            <input type="text" x-model="searchQuery" @input.debounce.400ms="applyFilters()"
                   placeholder="Ürün / Barkod ara..."
                   class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg pl-3 pr-4 py-2 w-full sm:w-52 placeholder-gray-400">
            <button @click="openNewMovement()"
                    class="bg-emerald-600 hover:bg-emerald-700 text-gray-900 font-medium rounded-lg text-sm px-5 py-2 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Stok Hareketi Ekle
            </button>
        </div>
    </div>

    {{-- Movements Table --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5">Tarih</th>
                        <th class="px-4 py-3.5">İşlem Kodu</th>
                        <th class="px-4 py-3.5">Ürün</th>
                        <th class="px-4 py-3.5 text-center">Tip</th>
                        <th class="px-4 py-3.5 text-right">Miktar</th>
                        <th class="px-4 py-3.5 text-right">Birim Fiyat</th>
                        <th class="px-4 py-3.5 text-right">Toplam</th>
                        <th class="px-4 py-3.5">Cari / Not</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($movements as $mov)
                        @php
                            $typeLabels = ['purchase'=>'Alış','sale'=>'Satış','return'=>'İade','adjustment'=>'Düzeltme','transfer'=>'Transfer','waste'=>'Fire'];
                            $typeColors = ['purchase'=>'text-emerald-600 bg-green-500/10','sale'=>'text-brand-500 bg-brand-500/10','return'=>'text-yellow-400 bg-yellow-500/10','adjustment'=>'text-purple-600 bg-purple-500/10','transfer'=>'text-cyan-400 bg-cyan-500/10','waste'=>'text-red-500 bg-red-500/10'];
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $mov->movement_date ? $mov->movement_date->format('d.m.Y H:i') : '-' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $mov->transaction_code ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $mov->product_name }}</p>
                                @if($mov->barcode)<span class="text-xs text-gray-500 font-mono">{{ $mov->barcode }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-medium px-2 py-1 rounded-full {{ $typeColors[$mov->type] ?? 'text-gray-500 bg-gray-500/10' }}">{{ $typeLabels[$mov->type] ?? $mov->type }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono {{ in_array($mov->type, ['purchase','return']) ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ in_array($mov->type, ['purchase','return']) ? '+' : '-' }}{{ number_format(abs($mov->quantity), 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-gray-500">{{ formatCurrency($mov->unit_price ?? 0) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-gray-900">{{ formatCurrency($mov->total ?? 0) }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $mov->firm_customer ?? $mov->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <p class="text-gray-500 text-sm">Stok hareketi bulunamadı</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $movements->links() }}</div>
        @endif
    </div>

    {{-- New Movement Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white rounded-xl border border-gray-100 shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-transition>
            <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Stok Hareketi Ekle</h2>
                <button @click="showModal = false" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="submitMovement()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Hareket Tipi <span class="text-red-500">*</span></label>
                    <select x-model="movForm.type" required class="w-full bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-4 py-2.5">
                        <option value="purchase">Alış (Stok Girişi)</option>
                        <option value="sale">Satış (Stok Çıkışı)</option>
                        <option value="return">İade</option>
                        <option value="adjustment">Düzeltme</option>
                        <option value="waste">Fire</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ürün ID <span class="text-red-500">*</span></label>
                    <input type="number" x-model="movForm.product_id" required class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5" placeholder="Ürün ID girin">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Miktar <span class="text-red-500">*</span></label>
                        <input type="number" x-model="movForm.quantity" step="0.01" required class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Birim Fiyat</label>
                        <input type="number" x-model="movForm.unit_price" step="0.01" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Cari / Firma</label>
                    <input type="text" x-model="movForm.firm_customer" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5" placeholder="Tedarikçi adı">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Not</label>
                    <input type="text" x-model="movForm.note" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5" placeholder="Açıklama">
                </div>
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg">İptal</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-900 bg-emerald-600 hover:bg-emerald-700 rounded-lg disabled:opacity-50">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function stockManager() {
    return {
        showModal: false, saving: false,
        filterType: new URLSearchParams(window.location.search).get('type') || '',
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        movForm: { type: 'purchase', product_id: '', quantity: '', unit_price: '', firm_customer: '', note: '' },
        openNewMovement() { this.movForm = { type: 'purchase', product_id: '', quantity: '', unit_price: '', firm_customer: '', note: '' }; this.showModal = true; },
        applyFilters() {
            const params = new URLSearchParams();
            if (this.filterType) params.set('type', this.filterType);
            if (this.searchQuery) params.set('search', this.searchQuery);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },
        async submitMovement() {
            this.saving = true;
            try {
                await posAjax('/stock', { method: 'POST', body: JSON.stringify(this.movForm) });
                showToast('Stok hareketi kaydedildi', 'success');
                this.showModal = false; window.location.reload();
            } catch (e) { showToast(e.message || 'Hata', 'error'); }
            finally { this.saving = false; }
        }
    };
}
</script>
@endpush
