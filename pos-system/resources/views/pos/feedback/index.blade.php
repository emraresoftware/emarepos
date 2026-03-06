@extends('pos.layouts.app')
@section('title', 'Geri Bildirimler')

@section('content')
<div class="p-6 space-y-6" x-data="feedbackAdmin()">

    {{-- Başlık --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">💬 Geri Bildirimler</h1>
            <p class="text-sm text-gray-500 mt-1">Kullanıcılardan gelen hata, öneri ve sorular</p>
        </div>
    </div>

    {{-- İstatistik Kartlar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center">
                    <i class="fa-solid fa-comments text-brand-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Toplam Bildirim</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i class="fa-solid fa-circle-exclamation text-amber-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['open'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Açık</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid fa-spinner text-blue-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['in_progress'] }}</p>
            <p class="text-xs text-gray-500 mt-1">İnceleniyor</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['resolved'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Çözüldü</p>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Durum</label>
                <select name="status" class="rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                    <option value="">Tümü</option>
                    <option value="open" {{ request('status')=='open' ? 'selected' : '' }}>Açık</option>
                    <option value="in_progress" {{ request('status')=='in_progress' ? 'selected' : '' }}>İnceleniyor</option>
                    <option value="resolved" {{ request('status')=='resolved' ? 'selected' : '' }}>Çözüldü</option>
                    <option value="closed" {{ request('status')=='closed' ? 'selected' : '' }}>Kapatıldı</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                <select name="category" class="rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                    <option value="">Tümü</option>
                    <option value="bug" {{ request('category')=='bug' ? 'selected' : '' }}>🐛 Hata</option>
                    <option value="suggestion" {{ request('category')=='suggestion' ? 'selected' : '' }}>💡 Öneri</option>
                    <option value="question" {{ request('category')=='question' ? 'selected' : '' }}>❓ Soru</option>
                    <option value="other" {{ request('category')=='other' ? 'selected' : '' }}>💬 Diğer</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Öncelik</label>
                <select name="priority" class="rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                    <option value="">Tümü</option>
                    <option value="critical" {{ request('priority')=='critical' ? 'selected' : '' }}>🔴 Kritik</option>
                    <option value="high" {{ request('priority')=='high' ? 'selected' : '' }}>🟠 Yüksek</option>
                    <option value="normal" {{ request('priority')=='normal' ? 'selected' : '' }}>🟡 Normal</option>
                    <option value="low" {{ request('priority')=='low' ? 'selected' : '' }}>🟢 Düşük</option>
                </select>
            </div>
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Ara</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Mesaj veya kullanıcı..."
                       class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
            </div>
            <button type="submit" class="px-5 py-2 bg-gradient-to-r from-brand-500 to-purple-600 text-white text-sm font-semibold rounded-xl hover:opacity-90 transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Filtrele
            </button>
            @if(request()->hasAny(['status','category','priority','search']))
            <a href="{{ route('pos.feedback') }}" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-200 transition">
                Temizle
            </a>
            @endif
        </form>
    </div>

    {{-- Tablo --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        @if($feedbacks->isEmpty())
        <div class="text-center py-16">
            <div class="text-5xl mb-4">💬</div>
            <p class="text-gray-500 text-sm">Henüz geri bildirim yok.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Mesaj</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Öncelik</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Durum</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tarih</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($feedbacks as $fb)
                    <tr class="hover:bg-gray-50/50 transition-colors" x-data="{}">
                        <td class="px-5 py-4 max-w-xs">
                            <p class="text-gray-800 font-medium text-sm line-clamp-2">{{ $fb->message }}</p>
                            @if($fb->user_name)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $fb->user_name }}</p>
                            @endif
                            @if($fb->page_url)
                            <a href="{{ $fb->page_url }}" target="_blank" class="text-xs text-brand-400 hover:underline truncate block max-w-[220px]" title="{{ $fb->page_url }}">
                                {{ parse_url($fb->page_url, PHP_URL_PATH) ?: $fb->page_url }}
                            </a>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @php
                            $catColors = ['bug'=>'bg-red-50 text-red-600','suggestion'=>'bg-blue-50 text-blue-600','question'=>'bg-purple-50 text-purple-600','other'=>'bg-gray-50 text-gray-600'];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $catColors[$fb->category] ?? 'bg-gray-50 text-gray-600' }}">
                                {{ $fb->category_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            @php
                            $priColors = ['critical'=>'bg-red-50 text-red-600','high'=>'bg-orange-50 text-orange-600','normal'=>'bg-yellow-50 text-yellow-700','low'=>'bg-green-50 text-green-600'];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $priColors[$fb->priority] ?? 'bg-gray-50 text-gray-600' }}">
                                {{ $fb->priority_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            @php
                            $statusColors = ['open'=>'bg-amber-50 text-amber-700','in_progress'=>'bg-blue-50 text-blue-700','resolved'=>'bg-emerald-50 text-emerald-700','closed'=>'bg-gray-100 text-gray-500'];
                            @endphp
                            <select
                                class="text-xs font-semibold px-2.5 py-1 rounded-lg border-0 outline-none cursor-pointer {{ $statusColors[$fb->status] ?? 'bg-gray-50 text-gray-600' }}"
                                onchange="updateStatus({{ $fb->id }}, this.value)"
                            >
                                @foreach(['open'=>'Açık','in_progress'=>'İnceleniyor','resolved'=>'Çözüldü','closed'=>'Kapatıldı'] as $val => $label)
                                <option value="{{ $val }}" {{ $fb->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-4 text-xs text-gray-400 whitespace-nowrap">
                            {{ $fb->created_at->format('d.m.Y') }}<br>
                            <span class="text-gray-400">{{ $fb->created_at->format('H:i') }}</span>
                            @if($fb->admin_reply)
                            <div class="mt-1 text-emerald-500 flex items-center gap-1 text-[11px]">
                                <i class="fa-solid fa-reply"></i> Yanıtlandı
                            </div>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="openReply({{ $fb->id }}, `{{ addslashes($fb->message) }}`, `{{ addslashes($fb->admin_reply ?? '') }}`)"
                                    class="px-3 py-1.5 bg-brand-50 text-brand-600 text-xs font-semibold rounded-lg hover:bg-brand-100 transition"
                                    title="Yanıtla">
                                    <i class="fa-solid fa-reply mr-1"></i>Yanıtla
                                </button>
                                <button
                                    @click="deleteFeedback({{ $fb->id }})"
                                    class="p-1.5 text-gray-300 hover:text-red-400 transition rounded-lg hover:bg-red-50"
                                    title="Sil">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Sayfalama --}}
        @if($feedbacks->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $feedbacks->links() }}
        </div>
        @endif
        @endif
    </div>

    {{-- Yanıt Modal --}}
    <div x-show="showReply" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none;">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showReply = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">💬 Geri Bildirim Yanıtla</h2>
                <button @click="showReply = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
                <p class="text-xs font-semibold text-gray-500 mb-1">Kullanıcının mesajı:</p>
                <p class="text-sm text-gray-700" x-text="replyData.message"></p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Yanıtınız</label>
                <textarea
                    x-model="replyData.admin_reply"
                    rows="4"
                    placeholder="Yanıt yazın..."
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none resize-none"
                ></textarea>
            </div>
            <div class="flex gap-3 justify-end pt-1">
                <button @click="showReply = false"
                        class="px-5 py-2.5 text-sm text-gray-600 font-semibold rounded-xl bg-gray-100 hover:bg-gray-200 transition">
                    İptal
                </button>
                <button @click="submitReply()"
                        :disabled="!replyData.admin_reply.trim() || replyLoading"
                        class="px-6 py-2.5 text-sm text-white font-semibold rounded-xl bg-gradient-to-r from-brand-500 to-purple-600 hover:opacity-90 transition disabled:opacity-50">
                    <span x-show="!replyLoading">Yanıtı Gönder</span>
                    <span x-show="replyLoading">Gönderiliyor...</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function feedbackAdmin() {
    return {
        showReply: false,
        replyLoading: false,
        replyData: { id: null, message: '', admin_reply: '' },

        openReply(id, message, existing) {
            this.replyData = { id, message, admin_reply: existing };
            this.showReply = true;
        },

        async submitReply() {
            if (!this.replyData.admin_reply.trim()) return;
            this.replyLoading = true;
            try {
                const res = await posAjax(`/feedback/${this.replyData.id}/reply`, {
                    method: 'POST',
                    body: JSON.stringify({ admin_reply: this.replyData.admin_reply }),
                });
                if (res.success) {
                    showToast('Yanıt gönderildi', 'success');
                    this.showReply = false;
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch (e) {
                showToast('Hata oluştu', 'error');
            } finally {
                this.replyLoading = false;
            }
        },

        async deleteFeedback(id) {
            if (!confirm('Bu geri bildirimi silmek istediğinize emin misiniz?')) return;
            try {
                const res = await posAjax(`/feedback/${id}`, { method: 'DELETE' });
                if (res.success) {
                    showToast('Silindi', 'success');
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch (e) {
                showToast('Hata oluştu', 'error');
            }
        },
    };
}

async function updateStatus(id, status) {
    try {
        await posAjax(`/feedback/${id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status }),
        });
        showToast('Durum güncellendi', 'success');
    } catch (e) {
        showToast('Hata oluştu', 'error');
    }
}
</script>
@endpush
