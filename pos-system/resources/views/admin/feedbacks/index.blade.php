@extends('admin.layout')

@section('title', 'Geri Bildirimler')
@section('page-title', 'Geri Bildirimler')
@section('page-sub', 'Tüm tenantlardan gelen mesajlar')

@section('content')

{{-- Filtreler --}}
<form method="GET" class="flex flex-wrap items-center gap-3 mb-6">
    <select name="status"
            class="bg-slate-800 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-brand-500">
        <option value="">Tüm Durumlar</option>
        <option value="open"    {{ request('status') === 'open'    ? 'selected' : '' }}>Açık</option>
        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>İşlemde</option>
        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Çözüldü</option>
        <option value="closed"  {{ request('status') === 'closed'  ? 'selected' : '' }}>Kapalı</option>
    </select>
    <select name="category"
            class="bg-slate-800 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-brand-500">
        <option value="">Tüm Kategoriler</option>
        <option value="bug"        {{ request('category') === 'bug'        ? 'selected' : '' }}>Hata</option>
        <option value="suggestion" {{ request('category') === 'suggestion' ? 'selected' : '' }}>Öneri</option>
        <option value="question"   {{ request('category') === 'question'   ? 'selected' : '' }}>Soru</option>
        <option value="other"      {{ request('category') === 'other'      ? 'selected' : '' }}>Diğer</option>
    </select>
    <button type="submit"
            class="bg-brand-600 hover:bg-brand-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-filter mr-1"></i> Filtrele
    </button>
    @if(request()->anyFilled(['status','category']))
        <a href="{{ route('admin.feedbacks') }}"
           class="text-slate-400 hover:text-white text-sm px-3 py-2 rounded-lg hover:bg-slate-700 transition-colors">
            <i class="fas fa-times mr-1"></i> Temizle
        </a>
    @endif
    <span class="ml-auto text-xs text-slate-500">{{ $feedbacks->total() }} kayıt</span>
</form>

{{-- Tablo --}}
<div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="text-xs text-slate-400 uppercase bg-slate-900/50 border-b border-slate-700">
            <tr>
                <th class="px-4 py-3">Mesaj</th>
                <th class="px-4 py-3">Kullanıcı</th>
                <th class="px-4 py-3">Kategori</th>
                <th class="px-4 py-3">Öncelik</th>
                <th class="px-4 py-3">Durum</th>
                <th class="px-4 py-3">Tarih</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
            @forelse($feedbacks as $fb)
                <tr class="hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3 max-w-xs">
                        <p class="text-slate-200 text-xs leading-relaxed line-clamp-2">{{ $fb->message }}</p>
                        @if($fb->page_url)
                            <p class="text-[10px] text-slate-600 mt-0.5 truncate">{{ $fb->page_url }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $fb->user_name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-700 text-slate-300">
                            {{ match($fb->category) {
                                'bug' => 'Hata', 'suggestion' => 'Öneri',
                                'question' => 'Soru', default => 'Diğer'
                            } }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $fb->priority === 'critical' ? 'bg-red-500/20 text-red-400' :
                               ($fb->priority === 'high'     ? 'bg-orange-500/20 text-orange-400' :
                               ($fb->priority === 'normal'   ? 'bg-blue-500/20 text-blue-400' :
                                                               'bg-slate-700 text-slate-400')) }}">
                            {{ match($fb->priority) {
                                'critical' => 'Kritik', 'high' => 'Yüksek',
                                'normal' => 'Normal', default => 'Düşük'
                            } }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $fb->status === 'open'        ? 'bg-amber-500/20 text-amber-400' :
                               ($fb->status === 'in_progress'? 'bg-blue-500/20 text-blue-400' :
                               ($fb->status === 'resolved'   ? 'bg-emerald-500/20 text-emerald-400' :
                                                               'bg-slate-700 text-slate-400')) }}">
                            {{ match($fb->status) {
                                'open' => 'Açık', 'in_progress' => 'İşlemde',
                                'resolved' => 'Çözüldü', default => 'Kapalı'
                            } }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $fb->created_at->format('d.m.Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                        <i class="fas fa-comment-slash text-2xl mb-2 block"></i>
                        Geri bildirim yok.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($feedbacks->hasPages())
    <div class="mt-5 flex justify-center">{{ $feedbacks->links() }}</div>
@endif

@endsection
