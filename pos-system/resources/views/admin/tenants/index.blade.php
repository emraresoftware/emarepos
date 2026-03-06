@extends('admin.layout')

@section('title', 'Tenantlar')
@section('page-title', 'Tenantlar')
@section('page-sub', 'Tüm kayıtlı işletmeler')

@section('content')

{{-- Filtreler --}}
<form method="GET" class="flex flex-wrap items-center gap-3 mb-6">
    <div class="relative">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="İşletme / slug / e-posta ara..."
               class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg pl-9 pr-4 py-2 w-64 focus:outline-none focus:border-brand-500 placeholder-slate-500">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
    </div>
    <select name="status"
            class="bg-slate-800 border border-slate-700 text-slate-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-brand-500">
        <option value="">Tüm Durumlar</option>
        <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Aktif</option>
        <option value="trial"     {{ request('status') === 'trial'     ? 'selected' : '' }}>Deneme</option>
        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Askıda</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>İptal</option>
    </select>
    <button type="submit"
            class="bg-brand-600 hover:bg-brand-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-filter mr-1"></i> Filtrele
    </button>
    @if(request()->anyFilled(['search','status']))
        <a href="{{ route('admin.tenants') }}"
           class="text-slate-400 hover:text-white text-sm px-3 py-2 rounded-lg hover:bg-slate-700 transition-colors">
            <i class="fas fa-times mr-1"></i> Temizle
        </a>
    @endif
    <span class="ml-auto text-xs text-slate-500">{{ $tenants->total() }} kayıt</span>
</form>

{{-- Tablo --}}
<div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="text-xs text-slate-400 uppercase bg-slate-900/50 border-b border-slate-700">
            <tr>
                <th class="px-4 py-3">İşletme</th>
                <th class="px-4 py-3">Plan</th>
                <th class="px-4 py-3">Kullanıcı</th>
                <th class="px-4 py-3">Durum</th>
                <th class="px-4 py-3">Süre Sonu</th>
                <th class="px-4 py-3">Kayıt</th>
                <th class="px-4 py-3 text-center">İşlem</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
            @forelse($tenants as $tenant)
                <tr class="hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div>
                            <p class="font-medium text-slate-100">{{ $tenant->name }}</p>
                            <p class="text-xs text-slate-500">{{ $tenant->slug }}</p>
                            @if($tenant->billing_email)
                                <p class="text-[10px] text-slate-600">{{ $tenant->billing_email }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-300">{{ $tenant->plan?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-300">{{ $tenant->users_count }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium
                            {{ $tenant->status === 'active'    ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' :
                               ($tenant->status === 'trial'    ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' :
                               ($tenant->status === 'suspended'? 'bg-red-500/20 text-red-400 border border-red-500/30' :
                                                                 'bg-slate-700 text-slate-400')) }}">
                            {{ match($tenant->status) {
                                'active'    => 'Aktif',
                                'trial'     => 'Deneme',
                                'suspended' => 'Askıda',
                                'cancelled' => 'İptal',
                                default     => $tenant->status,
                            } }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-xs">
                        {{ $tenant->trial_ends_at?->format('d.m.Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        {{ $tenant->created_at->format('d.m.Y') }}
                    </td>
                    <td class="px-4 py-3 text-center" x-data="{ open: false }">
                        <div class="relative inline-block">
                            <button @click="open = !open" @click.away="open = false"
                                    class="text-slate-400 hover:text-white text-xs border border-slate-700 hover:border-slate-500 px-2.5 py-1.5 rounded-lg transition-colors">
                                <i class="fas fa-ellipsis"></i>
                            </button>
                            <div x-show="open" x-cloak
                                 class="absolute right-0 mt-1 w-40 bg-slate-900 border border-slate-700 rounded-xl shadow-xl z-20 overflow-hidden">
                                @foreach([
                                    ['status' => 'active',    'label' => 'Aktif Yap',    'icon' => 'fa-check-circle',   'cls' => 'text-emerald-400'],
                                    ['status' => 'trial',     'label' => 'Denemeye Al',  'icon' => 'fa-hourglass-half', 'cls' => 'text-amber-400'],
                                    ['status' => 'suspended', 'label' => 'Askıya Al',    'icon' => 'fa-pause-circle',   'cls' => 'text-red-400'],
                                ] as $opt)
                                    @if($tenant->status !== $opt['status'])
                                        <form method="POST"
                                              action="{{ route('admin.tenants.status', $tenant) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $opt['status'] }}">
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2.5 text-xs {{ $opt['cls'] }} hover:bg-slate-800 flex items-center gap-2 transition-colors">
                                                <i class="fas {{ $opt['icon'] }} w-4"></i>
                                                {{ $opt['label'] }}
                                            </button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                        <i class="fas fa-building-circle-xmark text-2xl mb-2 block"></i>
                        Kayıt bulunamadı.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Sayfalama --}}
@if($tenants->hasPages())
    <div class="mt-5 flex justify-center">
        {{ $tenants->links() }}
    </div>
@endif

@endsection
