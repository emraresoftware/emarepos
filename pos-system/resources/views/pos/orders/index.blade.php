@extends('pos.layouts.app')

@section('title', 'Siparişler')

@section('content')
<div class="p-3 sm:p-6 overflow-y-auto h-full" x-data="orderManager()" x-cloak>
    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Bugün Toplam</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $stats['total_today'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Bekleyen</p>
            <p class="text-xl font-bold text-yellow-400 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Hazırlanan</p>
            <p class="text-xl font-bold text-brand-500 mt-1">{{ $stats['preparing'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Tamamlanan</p>
            <p class="text-xl font-bold text-emerald-600 mt-1">{{ $stats['completed'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">İptal</p>
            <p class="text-xl font-bold text-red-500 mt-1">{{ $stats['cancelled'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Ciro</p>
            <p class="text-xl font-bold text-emerald-400 mt-1">{{ formatCurrency($stats['total_revenue']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Siparişler</h1>
        <div class="flex flex-wrap items-center gap-3">
            <input type="date" x-model="filterDate" @change="applyFilters()"
                   class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-3 py-2 focus:ring-brand-500/20 focus:border-brand-500">
            <select x-model="filterStatus" @change="applyFilters()"
                    class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-3 py-2 focus:ring-brand-500/20 focus:border-brand-500">
                <option value="">Tüm Durumlar</option>
                <option value="pending">Bekleyen</option>
                <option value="preparing">Hazırlanıyor</option>
                <option value="ready">Hazır</option>
                <option value="served">Servis Edildi</option>
                <option value="completed">Tamamlandı</option>
                <option value="cancelled">İptal</option>
            </select>
            <select x-model="filterType" @change="applyFilters()"
                    class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg px-3 py-2 focus:ring-brand-500/20 focus:border-brand-500">
                <option value="">Tüm Tipler</option>
                <option value="dine_in">Restoran</option>
                <option value="takeaway">Paket</option>
                <option value="delivery">Kurye</option>
            </select>
            <div class="relative">
                <input type="text" x-model="searchQuery" @input.debounce.400ms="applyFilters()"
                       placeholder="Sipariş no ara..."
                       class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg pl-9 pr-4 py-2 w-full sm:w-52 focus:ring-brand-500/20 focus:border-brand-500 placeholder-gray-400">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5">Sipariş No</th>
                        <th class="px-4 py-3.5">Masa / Tip</th>
                        <th class="px-4 py-3.5">Müşteri</th>
                        <th class="px-4 py-3.5">Personel</th>
                        <th class="px-4 py-3.5 text-center">Ürün</th>
                        <th class="px-4 py-3.5 text-right">Tutar</th>
                        <th class="px-4 py-3.5 text-center">Durum</th>
                        <th class="px-4 py-3.5">Saat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click="showDetail({{ $order->id }})">
                            <td class="px-4 py-3">
                                <span class="font-mono font-medium text-gray-900">{{ $order->order_number ?? '#'.$order->id }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($order->tableSession && $order->tableSession->table)
                                    <span class="text-brand-500">{{ $order->tableSession->table->name ?? 'Masa' }}</span>
                                @else
                                    @php
                                        $typeLabels = ['dine_in' => 'Restoran', 'takeaway' => 'Paket', 'delivery' => 'Kurye'];
                                    @endphp
                                    <span class="text-gray-500">{{ $typeLabels[$order->order_type] ?? $order->order_type }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $order->customer->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $order->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded-full">{{ $order->total_items ?? $order->items->count() }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-gray-900">{{ formatCurrency($order->grand_total) }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                                        'preparing' => 'bg-brand-500/10 text-brand-500 border-blue-500/30',
                                        'ready' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                        'served' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
                                        'completed' => 'bg-green-500/10 text-emerald-600 border-green-500/30',
                                        'cancelled' => 'bg-red-500/10 text-red-500 border-red-500/30',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Bekliyor', 'preparing' => 'Hazırlanıyor', 'ready' => 'Hazır',
                                        'served' => 'Servis Edildi', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal',
                                    ];
                                @endphp
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full border {{ $statusColors[$order->status] ?? 'bg-gray-500/10 text-gray-500' }}">
                                    {{ $statusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->format('H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="text-gray-500 text-sm">Sipariş bulunamadı</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $orders->links() }}</div>
        @endif
    </div>

    <div x-show="showDetailModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition.opacity>
        <div class="absolute inset-0 bg-slate-950/60" @click="closeDetail()"></div>

        <div class="relative w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-3xl bg-white shadow-2xl shadow-black/20 border border-white/60">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div>
                    <p class="text-xs uppercase tracking-[0.24em] text-gray-500">Sipariş Detayı</p>
                    <h2 class="text-xl font-bold text-gray-900 mt-1" x-text="selectedOrder?.order_number || ('#' + (selectedOrder?.id || ''))"></h2>
                </div>
                <button @click="closeDetail()" class="w-10 h-10 rounded-full border border-gray-200 text-gray-500 hover:text-red-500 hover:border-red-200 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div x-show="detailLoading" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl mb-3"></i>
                <p>Detay yükleniyor...</p>
            </div>

            <div x-show="!detailLoading && !selectedOrder" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-circle-exclamation text-2xl mb-3"></i>
                <p>Sipariş detayı yüklenemedi.</p>
            </div>

            <div x-show="!detailLoading && selectedOrder" class="overflow-y-auto max-h-[calc(90vh-81px)]">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 p-6">
                    <div class="xl:col-span-2 space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs text-gray-500">Durum</p>
                                <p class="mt-2 inline-flex px-2.5 py-1 rounded-full text-xs font-semibold border"
                                   :class="statusBadgeClass(selectedOrder?.status)"
                                   x-text="statusLabel(selectedOrder?.status)"></p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs text-gray-500">Masa / Tip</p>
                                <p class="mt-2 text-sm font-semibold text-gray-900" x-text="selectedOrder?.table_session?.table?.name || orderTypeLabel(selectedOrder?.order_type)"></p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs text-gray-500">Müşteri</p>
                                <p class="mt-2 text-sm font-semibold text-gray-900" x-text="selectedOrder?.customer?.name || 'Müşteri seçilmemiş'"></p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs text-gray-500">Saat</p>
                                <p class="mt-2 text-sm font-semibold text-gray-900" x-text="formatDate(selectedOrder?.ordered_at)"></p>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 bg-white flex items-center justify-between">
                                <h3 class="text-base font-semibold text-gray-900">Sipariş Kalemleri</h3>
                                <span class="text-xs text-gray-500" x-text="(selectedOrder?.items?.length || 0) + ' ürün'"></span>
                            </div>

                            <div class="divide-y divide-gray-100 bg-white">
                                <template x-for="item in (selectedOrder?.items || [])" :key="item.id">
                                    <div class="px-5 py-4 flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="font-semibold text-gray-900" x-text="item.product_name"></p>
                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold border"
                                                      :class="statusBadgeClass(item.status)"
                                                      x-text="statusLabel(item.status)"></span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <span x-text="formatCurrency(item.unit_price)"></span>
                                                <span class="mx-1">×</span>
                                                <span x-text="Number(item.quantity || 0)"></span>
                                            </p>
                                            <template x-if="item.notes">
                                                <p class="text-xs text-amber-600 mt-2">
                                                    <i class="fas fa-note-sticky mr-1"></i><span x-text="item.notes"></span>
                                                </p>
                                            </template>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="font-semibold text-gray-900" x-text="formatCurrency(item.total)"></p>
                                            <p class="text-xs text-gray-500 mt-1">KDV: <span x-text="'%' + (item.vat_rate ?? 0)"></span></p>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="!(selectedOrder?.items?.length)">
                                    <div class="px-5 py-10 text-center text-sm text-gray-500">Bu siparişte ürün bulunmuyor.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-3xl border border-gray-100 bg-gray-50 p-5 space-y-4">
                            <h3 class="text-base font-semibold text-gray-900">Özet</h3>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Ara Toplam</span>
                                <span class="font-medium text-gray-900" x-text="formatCurrency(selectedOrder?.subtotal)"></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">KDV</span>
                                <span class="font-medium text-gray-900" x-text="formatCurrency(selectedOrder?.vat_total)"></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">İndirim</span>
                                <span class="font-medium text-gray-900" x-text="formatCurrency(selectedOrder?.discount_total)"></span>
                            </div>
                            <div class="pt-4 border-t border-gray-200 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900">Genel Toplam</span>
                                <span class="text-lg font-bold text-emerald-600" x-text="formatCurrency(selectedOrder?.grand_total)"></span>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-gray-100 bg-white p-5 space-y-3">
                            <h3 class="text-base font-semibold text-gray-900">Ek Bilgiler</h3>
                            <div class="text-sm">
                                <p class="text-gray-500">Personel</p>
                                <p class="font-medium text-gray-900 mt-1" x-text="selectedOrder?.user?.name || '-'"></p>
                            </div>
                            <div class="text-sm">
                                <p class="text-gray-500">Mutfak Notu</p>
                                <p class="font-medium text-gray-900 mt-1" x-text="selectedOrder?.kitchen_notes || 'Not yok'"></p>
                            </div>
                            <div class="text-sm">
                                <p class="text-gray-500">Sipariş Notu</p>
                                <p class="font-medium text-gray-900 mt-1" x-text="selectedOrder?.notes || 'Not yok'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function orderManager() {
    return {
        filterDate: new URLSearchParams(window.location.search).get('date') || '',
        filterStatus: new URLSearchParams(window.location.search).get('status') || '',
        filterType: new URLSearchParams(window.location.search).get('order_type') || '',
        searchQuery: new URLSearchParams(window.location.search).get('search') || '',
        showDetailModal: false,
        detailLoading: false,
        selectedOrder: null,
        applyFilters() {
            const params = new URLSearchParams();
            if (this.filterDate) params.set('date', this.filterDate);
            if (this.filterStatus) params.set('status', this.filterStatus);
            if (this.filterType) params.set('order_type', this.filterType);
            if (this.searchQuery) params.set('search', this.searchQuery);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },
        closeDetail() {
            this.showDetailModal = false;
            this.detailLoading = false;
            this.selectedOrder = null;
        },
        statusLabel(status) {
            const labels = {
                pending: 'Bekliyor',
                preparing: 'Hazırlanıyor',
                ready: 'Hazır',
                served: 'Servis Edildi',
                completed: 'Tamamlandı',
                cancelled: 'İptal',
                paid: 'Ödendi',
            };

            return labels[status] || status || '-';
        },
        statusBadgeClass(status) {
            const classes = {
                pending: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/30',
                preparing: 'bg-brand-500/10 text-brand-500 border-blue-500/30',
                ready: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30',
                served: 'bg-cyan-500/10 text-cyan-500 border-cyan-500/30',
                completed: 'bg-green-500/10 text-emerald-600 border-green-500/30',
                cancelled: 'bg-red-500/10 text-red-500 border-red-500/30',
                paid: 'bg-slate-500/10 text-slate-600 border-slate-500/30',
            };

            return classes[status] || 'bg-gray-100 text-gray-600 border-gray-200';
        },
        orderTypeLabel(type) {
            const labels = {
                dine_in: 'Restoran',
                takeaway: 'Paket',
                delivery: 'Kurye',
            };

            return labels[type] || type || '-';
        },
        formatCurrency(value) {
            const amount = Number(value || 0);
            return amount.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' });
        },
        formatDate(value) {
            if (!value) return '-';

            return new Intl.DateTimeFormat('tr-TR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }).format(new Date(value));
        },
        async showDetail(id) {
            try {
                this.showDetailModal = true;
                this.detailLoading = true;
                this.selectedOrder = null;

                const res = await posAjax(`{{ url('/orders') }}/${id}`, {}, 'GET');
                this.selectedOrder = res.order || null;
            } catch (e) {
                showToast('Detay yüklenemedi', 'error');
                this.closeDetail();
            } finally {
                this.detailLoading = false;
            }
        }
    };
}
</script>
@endpush
