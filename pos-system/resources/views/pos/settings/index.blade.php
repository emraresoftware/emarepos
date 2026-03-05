@extends('pos.layouts.app')

@section('title', 'Ayarlar')

@section('content')
<div x-data="{ activeTab: 'branch' }" class="p-6 overflow-y-auto h-full">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Ayarlar</h1>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-6 border-b border-gray-100 pb-3">
        <button @click="activeTab = 'branch'" :class="activeTab === 'branch' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-store mr-1"></i> Şube Bilgileri
        </button>
        <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-cog mr-1"></i> Genel Ayarlar
        </button>
        <button @click="activeTab = 'payment'" :class="activeTab === 'payment' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-credit-card mr-1"></i> Ödeme Tipleri
        </button>
        <button @click="activeTab = 'tax'" :class="activeTab === 'tax' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-gray-900' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-percent mr-1"></i> Vergi Oranları
        </button>
    </div>

    {{-- Branch Settings Tab --}}
    <div x-show="activeTab === 'branch'" x-transition>
        <form method="POST" action="{{ url('/settings/branch') }}" class="bg-white rounded-xl border border-gray-100 p-6 space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Şube Adı</label>
                    <input type="text" name="name" value="{{ $branch->name ?? '' }}" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Telefon</label>
                    <input type="text" name="phone" value="{{ $branch->phone ?? '' }}" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-500 mb-1">Adres</label>
                    <textarea name="address" rows="2" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $branch->address ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Şehir</label>
                    <input type="text" name="city" value="{{ $branch->city ?? '' }}" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">İlçe</label>
                    <input type="text" name="district" value="{{ $branch->district ?? '' }}" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-gray-900 text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Kaydet
                </button>
            </div>
        </form>
    </div>

    {{-- General Settings Tab --}}
    <div x-show="activeTab === 'general'" x-transition>
        <form method="POST" action="{{ url('/settings/general') }}" class="bg-white rounded-xl border border-gray-100 p-6 space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Fiş Üst Yazı</label>
                    <textarea name="receipt_header" rows="3" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $tenant->meta['receipt_header'] ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Fiş Alt Yazı</label>
                    <textarea name="receipt_footer" rows="3" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $tenant->meta['receipt_footer'] ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Para Birimi Sembolü</label>
                    <input type="text" name="currency_symbol" value="{{ $tenant->meta['currency_symbol'] ?? '₺' }}" class="w-full bg-white border border-gray-700 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 space-y-4">
                <h3 class="text-sm font-medium text-gray-700">Seçenekler</h3>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="tax_included" value="1" {{ ($tenant->meta['tax_included'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-600 text-blue-600 focus:ring-brand-500/20">
                    Fiyatlara KDV dahil
                </label>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="auto_print_receipt" value="1" {{ ($tenant->meta['auto_print_receipt'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-600 text-blue-600 focus:ring-brand-500/20">
                    Satış sonrası otomatik fiş yazdır
                </label>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="kitchen_print" value="1" {{ ($tenant->meta['kitchen_print'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-600 text-blue-600 focus:ring-brand-500/20">
                    Mutfak yazıcısı aktif
                </label>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-gray-900 text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Kaydet
                </button>
            </div>
        </form>
    </div>

    {{-- Payment Types Tab --}}
    <div x-show="activeTab === 'payment'" x-transition>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3.5">Ad</th>
                            <th class="px-4 py-3.5">Kod</th>
                            <th class="px-4 py-3.5 text-center">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($paymentTypes as $pt)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-gray-900">{{ $pt->name }}</td>
                                <td class="px-4 py-3 font-mono text-gray-500">{{ $pt->code ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($pt->is_active ?? true)
                                        <span class="text-xs bg-green-500/10 text-emerald-600 px-2.5 py-1 rounded-full border border-green-500/30">Aktif</span>
                                    @else
                                        <span class="text-xs bg-red-500/10 text-red-500 px-2.5 py-1 rounded-full border border-red-500/30">Pasif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">Ödeme tipi bulunamadı</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tax Rates Tab --}}
    <div x-show="activeTab === 'tax'" x-transition>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3.5">Vergi Adı</th>
                            <th class="px-4 py-3.5 text-right">Oran (%)</th>
                            <th class="px-4 py-3.5 text-center">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($taxRates as $tax)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-gray-900">{{ $tax->name }}</td>
                                <td class="px-4 py-3 text-right font-mono text-gray-900">%{{ $tax->rate }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($tax->is_active ?? true)
                                        <span class="text-xs bg-green-500/10 text-emerald-600 px-2.5 py-1 rounded-full border border-green-500/30">Aktif</span>
                                    @else
                                        <span class="text-xs bg-red-500/10 text-red-500 px-2.5 py-1 rounded-full border border-red-500/30">Pasif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">Vergi oranı bulunamadı</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
