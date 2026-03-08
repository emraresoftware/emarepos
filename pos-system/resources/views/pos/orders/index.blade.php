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
        applyFilters() {
            const params = new URLSearchParams();
            if (this.filterDate) params.set('date', this.filterDate);
            if (this.filterStatus) params.set('status', this.filterStatus);
            if (this.filterType) params.set('order_type', this.filterType);
            if (this.searchQuery) params.set('search', this.searchQuery);
            window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        },
        async showDetail(id) {
            try {
                const res = await posAjax(`/orders/${id}`, {}, 'GET');
                // TODO: open detail modal
                showToast('Sipariş #' + id + ' detayı yüklendi', 'info');
            } catch (e) {
                showToast('Detay yüklenemedi', 'error');
            }
        }
    };
}
</script>
@endpush
