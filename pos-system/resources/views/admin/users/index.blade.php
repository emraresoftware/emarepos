@extends('admin.layout')

@section('title', 'Kullanıcılar')
@section('page-title', 'Kullanıcılar')
@section('page-sub', 'Tüm platform kullanıcıları')

@section('content')

{{-- Arama --}}
<form method="GET" class="flex flex-wrap items-center gap-3 mb-6">
    <div class="relative">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Ad / e-posta ara..."
               class="bg-slate-800 border border-slate-700 text-slate-200 text-sm rounded-lg pl-9 pr-4 py-2 w-64 focus:outline-none focus:border-brand-500 placeholder-slate-500">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
    </div>
    <button type="submit"
            class="bg-brand-600 hover:bg-brand-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-filter mr-1"></i> Ara
    </button>
    @if(request()->filled('search'))
        <a href="{{ route('admin.users') }}"
           class="text-slate-400 hover:text-white text-sm px-3 py-2 rounded-lg hover:bg-slate-700 transition-colors">
            <i class="fas fa-times mr-1"></i> Temizle
        </a>
    @endif
    <span class="ml-auto text-xs text-slate-500">{{ $users->total() }} kullanıcı</span>
</form>

{{-- Tablo --}}
<div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="text-xs text-slate-400 uppercase bg-slate-900/50 border-b border-slate-700">
            <tr>
                <th class="px-4 py-3">Kullanıcı</th>
                <th class="px-4 py-3">Tenant</th>
                <th class="px-4 py-3">Rol</th>
                <th class="px-4 py-3">Tür</th>
                <th class="px-4 py-3">Kayıt</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
            @forelse($users as $user)
                <tr class="hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-brand-900/60 flex items-center justify-center text-xs font-bold text-brand-400">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-medium text-slate-100">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $user->tenant?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $user->role?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($user->is_super_admin)
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-brand-500/20 text-brand-400 border border-brand-500/30 font-medium">
                                Süper Admin
                            </span>
                        @else
                            <span class="text-xs text-slate-500">Standart</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $user->created_at->format('d.m.Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                        <i class="fas fa-user-slash text-2xl mb-2 block"></i>
                        Kullanıcı bulunamadı.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
    <div class="mt-5 flex justify-center">{{ $users->links() }}</div>
@endif

@endsection
