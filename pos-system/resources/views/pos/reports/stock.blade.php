@extends('pos.layouts.app')
@section('title', 'Stok Raporu')

@section('content')
<div class="flex-1 overflow-y-auto p-4 space-y-4">

    {{-- Başlık + Filtreler --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Stok Raporu</h1>
            <p class="text-sm text-gray-500">Mevcut stok seviyeleri ve değerleri</p>
        </div>
        <form method="GET" action="{{ route('pos.reports.stock') }}" class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-xl px-3 py-2">
                <i class="fas fa-search text-gray-400 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Ürün ara..."
                       class="bg-transparent text-sm text-gray-900 focus:outline-none w-36">
            </div>
            <select name="category_id" class="bg-white border border-gray-200 text-sm text-gray-700 rounded-xl px-3 py-2 focus:outline-none focus:border-brand-400">
                <option value="">Tüm Kategoriler</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $catFilter == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="sort" class="bg-white border border-gray-200 text-sm text-gray-700 rounded-xl px-3 py-2 focus:outline-none focus:border-brand-400">
                <option value="name" {{ $sortBy === 'name' ? 'selected' : '' }}>Ada Göre</option>
                <option value="stock_asc" {{ $sortBy === 'stock_asc' ? 'selected' : '' }}>Stok (Az→Çok)</option>
                <option value="stock_desc" {{ $sortBy === 'stock_desc' ? 'selected' : '' }}>Stok (Çok→Az)</option>
                <option value="value" {{ $sortBy === 'value' ? 'selected' : '' }}>Değer (Çok→Az)</option>
            </select>
            <label class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-xl px-3 py-2 cursor-pointer text-sm text-gray-700">
                <input type="checkbox" name="low_stock" value="1" {{ $lowOnly ? 'checked' : '' }} class="rounded">
                Kritik Stok
            </label>
            <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-medium transition-colors">
                <i class="fas fa-filter mr-1"></i> Filtrele
            </button>
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition-colors">
                <i class="fas fa-print mr-1"></i> Yazdır
            </button>
        </form>
    </div>

    {{-- Özet Kartlar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-400">Toplam Ürün</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_products'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-red-100 p-4 shadow-sm">
            <p class="text-xs text-red-500">Kritik Stok</p>
            <p class="text-2xl font-bold text-red-500 mt-1">{{ $stats['low_stock'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-orange-100 p-4 shadow-sm">
            <p class="text-xs text-orange-500">Stok Yok (0 veya eksi)</p>
            <p class="text-2xl font-bold text-orange-500 mt-1">{{ $stats['zero_stock'] }}</p>
        </div>
        <div class="bg-gradient-to-br from-brand-500 to-purple-600 rounded-2xl p-4 shadow-sm text-white">
            <p class="text-xs text-white/70">Toplam Stok Değeri</p>
            <p class="text-xl font-bold mt-1">{{ formatCurrency($stats['total_value']) }}</p>
            <p class="text-xs text-white/70 mt-0.5">Alış fiyatı üzerinden</p>
        </div>
    </div>

    {{-- Stok Tablosu --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">
                <i class="fas fa-warehouse mr-1.5 text-brand-500"></i>
                Ürün Listesi ({{ count($products) }} ürün)
            </h2>
        </div>
        @if(count($products) === 0)
            <div class="text-center py-16 text-gray-400">
                <i class="fas fa-box-open text-4xl mb-3"></i>
                <p class="text-sm">Filtrelerinize uygun ürün bulunamadı.</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Ürün</th>
                        <th class="px-4 py-3 hidden sm:table-cell">Kategori</th>
                        <th class="px-4 py-3 hidden md:table-cell">Barkod / Kod</th>
                        <th class="px-4 py-3 text-right">Alış Fiyatı</th>
                        <th class="px-4 py-3 text-right">Satış Fiyatı</th>
                        <th class="px-4 py-3 text-right">Kritik Stok</th>
                        <th class="px-4 py-3 text-right">Mevcut Stok</th>
                        <th class="px-4 py-3 text-right hidden lg:table-cell">Stok Değeri</th>
                        <th class="px-4 py-3 text-center">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($products as $p)
                    @php
                        $isZero = $p['stock'] <= 0;
                        $isLow  = $p['is_low'];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $isZero ? 'bg-red-50/30' : ($isLow ? 'bg-orange-50/30' : '') }}">
                        <td class="px-4 py-2.5 font-medium text-gray-900">{{ $p['name'] }}</td>
                        <td class="px-4 py-2.5 text-gray-500 hidden sm:table-cell">
                            <span class="text-xs bg-gray-100 px-2 py-0.5 rounded-full">{{ $p['category'] }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-400 font-mono text-xs hidden md:table-cell">
                            {{ $p['barcode'] ?: ($p['stock_code'] ?: '-') }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-gray-600">{{ formatCurrency($p['purchase_price']) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-gray-900 font-medium">{{ formatCurrency($p['sale_price']) }}</td>
                        <td class="px-4 py-2.5 text-right text-gray-500 font-mono text-xs">{{ number_format($p['critical_stock'], 2) }} {{ $p['unit'] }}</td>
                        <td class="px-4 py-2.5 text-right font-mono font-bold {{ $isZero ? 'text-red-600' : ($isLow ? 'text-orange-500' : 'text-gray-900') }}">
                            {{ number_format($p['stock'], 2) }} {{ $p['unit'] }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-gray-700 hidden lg:table-cell">
                            {{ formatCurrency($p['stock_value']) }}
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            @if($isZero)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    <i class="fas fa-times-circle text-[10px]"></i> Tükenmiş
                                </span>
                            @elseif($isLow)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                    <i class="fas fa-exclamation-triangle text-[10px]"></i> Kritik
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                    <i class="fas fa-check text-[10px]"></i> Normal
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
