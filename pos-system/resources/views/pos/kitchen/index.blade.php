@extends('pos.layouts.app')
@section('title', 'Mutfak Ekranı')

@section('content')
<div x-data="kitchenScreen()" x-init="init()" class="flex-1 flex flex-col overflow-hidden">

    {{-- Üst Bar --}}
    <div class="p-3 sm:p-4 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 shrink-0">
                <i class="fas fa-fire-burner text-orange-400 mr-2"></i>Mutfak
            </h1>

            <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg p-1">
                <button @click="nameSource = 'order_user'"
                        class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors"
                        :class="nameSource === 'order_user' ? 'bg-brand-500 text-white' : 'text-gray-500 hover:text-gray-700'">
                    Siparisi Alan
                </button>
                <button @click="nameSource = 'table_opened_by'"
                        class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors"
                        :class="nameSource === 'table_opened_by' ? 'bg-brand-500 text-white' : 'text-gray-500 hover:text-gray-700'">
                    Masa Acan
                </button>
            </div>

            {{-- Durum Filtreleri --}}
            <div class="flex gap-1.5 flex-wrap">
                <button @click="statusFilter = 'all'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="statusFilter === 'all' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100 hover:text-gray-700'">
                    Tümü
                    <span class="ml-1 bg-black/10 px-1.5 py-0.5 rounded-full text-[10px]" x-text="counts.all"></span>
                </button>
                <button @click="statusFilter = 'pending'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="statusFilter === 'pending' ? 'bg-brand-500 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100 hover:text-gray-700'">
                    <i class="fas fa-clock mr-1"></i>Bekliyor
                    <span class="ml-1 bg-black/10 px-1.5 py-0.5 rounded-full text-[10px]" x-text="counts.pending"></span>
                </button>
                <button @click="statusFilter = 'preparing'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="statusFilter === 'preparing' ? 'bg-amber-500 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100 hover:text-gray-700'">
                    <i class="fas fa-utensils mr-1"></i>Hazırlanıyor
                    <span class="ml-1 bg-black/10 px-1.5 py-0.5 rounded-full text-[10px]" x-text="counts.preparing"></span>
                </button>
                <button @click="statusFilter = 'ready'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="statusFilter === 'ready' ? 'bg-emerald-500 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100 hover:text-gray-700'">
                    <i class="fas fa-check mr-1"></i>Hazır
                    <span class="ml-1 bg-black/20 px-1.5 py-0.5 rounded-full text-[10px]" x-text="counts.ready"></span>
                </button>
            </div>

            {{-- Personel Filtresi --}}
            <div class="flex items-center">
                @php
                    $activeUsers = collect();
                    foreach($orders as $o) {
                        if($o->user) $activeUsers->push($o->user);
                    }
                    $activeUsers = $activeUsers->unique('id');
                @endphp
                <select x-model="userFilter" class="px-2 py-1.5 h-8 rounded-lg text-xs font-medium border border-gray-200 bg-white text-gray-700 outline-none focus:border-brand-500 hover:bg-gray-50 transition-colors cursor-pointer" title="Siparişi Veren Personel">
                    <option value="all">Tüm Personeller</option>
                    @foreach($activeUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
            {{-- Ses Bildirimi --}}
            <button @click="soundEnabled = !soundEnabled"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    :class="soundEnabled ? 'bg-emerald-500 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-100 hover:text-gray-700'"
                    :title="soundEnabled ? 'Ses bildirimi açık' : 'Ses bildirimi kapalı'">
                <i class="fas" :class="soundEnabled ? 'fa-volume-high' : 'fa-volume-xmark'"></i>
            </button>

            {{-- Otomatik Yenileme Göstergesi --}}
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                </span>
                <span>Otomatik yenileme: <span x-text="countdown" class="text-gray-900 font-medium"></span>s</span>
            </div>

            {{-- Manuel Yenile --}}
            <button @click="refreshPage()" class="px-3 py-1.5 bg-white hover:bg-gray-100 border border-gray-200 rounded-lg text-xs text-gray-600 transition-colors">
                <i class="fas fa-arrows-rotate mr-1"></i>Yenile
            </button>
        </div>
    </div>

    {{-- Sipariş Kartları Grid --}}
    <div class="flex-1 overflow-y-auto p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($orders as $order)
            <div x-show="(statusFilter === 'all' || statusFilter === '{{ $order->status }}') && (userFilter === 'all' || userFilter == '{{ $order->user_id }}')"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white rounded-xl border-2 flex flex-col transition-all duration-200 hover:scale-[1.02] hover:shadow-xl hover:shadow-black/20
                    {{ $order->status === 'pending' ? 'border-blue-500/70' : ($order->status === 'preparing' ? 'border-amber-500/70' : 'border-green-500/70') }}"
                 data-order-id="{{ $order->id }}"
                 data-order-status="{{ $order->status }}">

                {{-- Kart Başlığı --}}
                <div class="p-3 border-b border-gray-100 flex items-center justify-between
                    {{ $order->status === 'pending' ? 'bg-brand-500/10' : ($order->status === 'preparing' ? 'bg-amber-50' : 'bg-green-500/10') }}">
                    <div class="flex items-center gap-2">
                        {{-- Sipariş No --}}
                        <span class="text-gray-900 font-bold text-sm">#{{ $order->order_number }}</span>
                        {{-- Masa --}}
                        @if($order->tableSession && $order->tableSession->table)
                        <span class="px-2 py-0.5 bg-gray-100 rounded text-xs text-gray-700">
                            <i class="fas fa-utensils mr-1 text-gray-300"></i>{{ ($order->tableSession->table->region->name ?? '') ? ($order->tableSession->table->region->name . ' ') : '' }}{{ $order->tableSession->table->name ?? ('Masa ' . $order->tableSession->table->table_no) }}
                        </span>
                        @endif
                        <span class="px-2 py-0.5 bg-slate-100 rounded text-xs text-gray-600">
                            <i class="fas fa-user mr-1 text-gray-400"></i>
                            <span x-show="nameSource === 'order_user'">{{ $order->user?->name ?? 'Bilinmiyor' }}</span>
                            <span x-show="nameSource === 'table_opened_by'">{{ $order->tableSession?->openedBy?->name ?? 'Bilinmiyor' }}</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Durum Rozeti --}}
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider
                            {{ $order->status === 'pending' ? 'bg-brand-500/20 text-brand-600' : ($order->status === 'preparing' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-500') }}">
                            {{ $order->status === 'pending' ? 'Bekliyor' : ($order->status === 'preparing' ? 'Hazırlanıyor' : 'Hazır') }}
                        </span>
                        {{-- Geçen Süre --}}
                        <span class="text-xs font-mono {{ $order->created_at->diffInMinutes(now()) > 15 ? 'text-red-500 font-bold' : ($order->created_at->diffInMinutes(now()) > 10 ? 'text-amber-600' : 'text-gray-500') }}"
                              title="{{ $order->created_at->format('H:i:s') }}">
                            <i class="fas fa-stopwatch mr-0.5"></i>{{ $order->created_at->diffInMinutes(now()) }}dk
                        </span>
                    </div>
                </div>

                {{-- Sipariş Kalemleri --}}
                <div class="flex-1 p-3 space-y-1.5 overflow-y-auto max-h-64">
                    @foreach($order->items as $item)
                    <div class="flex items-start gap-2 group py-1 {{ $item->status === 'ready' ? 'opacity-50' : '' }}"
                         data-item-id="{{ $item->id }}">
                        {{-- Checkbox --}}
                        <button @click="toggleItemReady({{ $item->id }}, '{{ $item->status }}')"
                                class="mt-0.5 w-5 h-5 rounded border flex-shrink-0 flex items-center justify-center transition-colors
                                    {{ $item->status === 'ready' ? 'bg-emerald-500 border-green-500 text-gray-900' : 'border-gray-200 hover:border-green-400 text-transparent hover:text-emerald-600' }}">
                            <i class="fas fa-check text-[10px]"></i>
                        </button>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-sm {{ $item->status === 'ready' ? 'line-through text-gray-500' : 'text-gray-900' }}">
                                    {{ $item->product_name }}
                                </span>
                                <span class="text-sm font-bold {{ $item->status === 'ready' ? 'text-gray-500' : 'text-brand-600' }} ml-2 flex-shrink-0">
                                    x{{ $item->quantity }}
                                </span>
                            </div>
                            @if($item->notes)
                            <p class="text-xs text-amber-600 mt-0.5 truncate" title="{{ $item->notes }}">
                                <i class="fas fa-sticky-note mr-0.5"></i>{{ $item->notes }}
                            </p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Kart Aksiyonları --}}
                <div class="p-3 border-t border-gray-100 flex items-center gap-2">
                    @if($order->status === 'pending')
                    <button @click="updateOrderStatus({{ $order->id }}, 'preparing')"
                            class="flex-1 py-2 bg-amber-600 hover:bg-amber-500 text-gray-900 text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-1.5">
                        <i class="fas fa-fire"></i>
                        Hazırlığa Başla
                    </button>
                    @elseif($order->status === 'preparing')
                    <button @click="updateOrderStatus({{ $order->id }}, 'ready')"
                            class="flex-1 py-2 bg-emerald-500 hover:bg-green-500 text-gray-900 text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-1.5">
                        <i class="fas fa-check-double"></i>
                        Hazır
                    </button>
                    @else
                    <div class="flex-1 py-2 text-center text-xs text-emerald-600 font-medium">
                        <i class="fas fa-circle-check mr-1"></i>Tamamlandı
                    </div>
                    @endif

                    {{-- Geçen süre detayı --}}
                    <div class="text-[10px] text-gray-500 text-right">
                        {{ $order->created_at->format('H:i') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Boş Durum --}}
        @if($orders->isEmpty())
        <div class="flex flex-col items-center justify-center h-full text-gray-500">
            <i class="fas fa-kitchen-set text-6xl mb-4 text-gray-600"></i>
            <p class="text-xl font-medium mb-1">Aktif sipariş yok</p>
            <p class="text-sm">Yeni siparişler burada görünecek</p>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function kitchenScreen() {
    return {
        statusFilter: 'all',
        userFilter: 'all',
        nameSource: 'order_user',
        countdown: 15,
        soundEnabled: false,
        refreshInterval: null,
        countdownInterval: null,
        previousOrderCount: {{ $orders->count() }},

        counts: {
            all: {{ $orders->count() }},
            pending: {{ $orders->where('status', 'pending')->count() }},
            preparing: {{ $orders->where('status', 'preparing')->count() }},
            ready: {{ $orders->where('status', 'ready')->count() }},
        },

        init() {
            this.startAutoRefresh();
        },

        startAutoRefresh() {
            // Countdown timer
            this.countdownInterval = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.refreshPage();
                }
            }, 1000);
        },

        refreshPage() {
            this.countdown = 15;
            location.reload();
        },

        async updateOrderStatus(orderId, status) {
            try {
                const res = await posAjax(`{{ url('/kitchen/order') }}/${orderId}/status`, { status }, 'POST');
                showToast(res.message || 'Sipariş durumu güncellendi', 'success');

                // Update card visually
                const card = document.querySelector(`[data-order-id="${orderId}"]`);
                if (card) {
                    card.dataset.orderStatus = status;

                    // Brief delay then reload to get fresh data
                    setTimeout(() => this.refreshPage(), 500);
                }
            } catch (e) {
                showToast(e.message || 'Bir hata oluştu', 'error');
            }
        },

        async toggleItemReady(itemId, currentStatus) {
            const newStatus = currentStatus === 'ready' ? 'pending' : 'ready';
            try {
                const res = await posAjax(`{{ url('/kitchen/item') }}/${itemId}/status`, { status: newStatus }, 'POST');
                showToast(res.message || 'Ürün durumu güncellendi', 'success');

                // Update item visually
                const itemEl = document.querySelector(`[data-item-id="${itemId}"]`);
                if (itemEl) {
                    if (newStatus === 'ready') {
                        itemEl.classList.add('opacity-50');
                        const checkbox = itemEl.querySelector('button');
                        checkbox.classList.add('bg-emerald-500', 'border-green-500', 'text-gray-900');
                        checkbox.classList.remove('border-gray-200', 'text-transparent');
                        const nameEl = itemEl.querySelector('span');
                        nameEl.classList.add('line-through', 'text-gray-500');
                        nameEl.classList.remove('text-gray-900');
                    } else {
                        itemEl.classList.remove('opacity-50');
                        const checkbox = itemEl.querySelector('button');
                        checkbox.classList.remove('bg-emerald-500', 'border-green-500', 'text-gray-900');
                        checkbox.classList.add('border-gray-200', 'text-transparent');
                        const nameEl = itemEl.querySelector('span');
                        nameEl.classList.remove('line-through', 'text-gray-500');
                        nameEl.classList.add('text-gray-900');
                    }
                }
            } catch (e) {
                showToast(e.message || 'Bir hata oluştu', 'error');
            }
        },

        playNotificationSound() {
            if (!this.soundEnabled) return;
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);

                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
                oscillator.frequency.setValueAtTime(1100, audioCtx.currentTime + 0.1);
                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime + 0.2);

                gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);

                oscillator.start(audioCtx.currentTime);
                oscillator.stop(audioCtx.currentTime + 0.4);
            } catch (e) {
                // Audio notification not supported
            }
        },

        destroy() {
            if (this.refreshInterval) clearInterval(this.refreshInterval);
            if (this.countdownInterval) clearInterval(this.countdownInterval);
        }
    };
}
</script>
@endpush
