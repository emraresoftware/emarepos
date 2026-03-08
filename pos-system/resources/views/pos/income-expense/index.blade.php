@extends('pos.layouts.app')
@section('title', 'Gelir / Gider')

@section('content')
<div x-data="incomeExpenseApp()" x-init="init()" class="flex-1 overflow-y-auto p-5 space-y-5" x-cloak>

    {{-- ── Başlık ── --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gelir / Gider</h1>
            <p class="text-sm text-gray-500 mt-0.5">Nakit akışını takip edin</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="openModal('income')"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-white
                           bg-gradient-to-r from-emerald-500 to-teal-500
                           shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40
                           hover:scale-105 transition-all duration-200">
                <i class="fas fa-plus mr-1.5"></i> Gelir Ekle
            </button>
            <button @click="openModal('expense')"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-white
                           bg-gradient-to-r from-red-500 to-rose-500
                           shadow-lg shadow-red-500/20 hover:shadow-red-500/40
                           hover:scale-105 transition-all duration-200">
                <i class="fas fa-minus mr-1.5"></i> Gider Ekle
            </button>
        </div>
    </div>

    {{-- ── İstatistik Kartları ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Toplam Gelir</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-arrow-trend-up text-emerald-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-emerald-600" x-text="formatMoney(stats.totalIncome) + ' ₺'"></div>
            <div class="text-xs text-gray-400 mt-1">Seçili dönemde</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Toplam Gider</span>
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                    <i class="fas fa-arrow-trend-down text-red-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold text-red-500" x-text="formatMoney(stats.totalExpense) + ' ₺'"></div>
            <div class="text-xs text-gray-400 mt-1">Seçili dönemde</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Net Bakiye</span>
                <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                     :class="netBalance >= 0 ? 'bg-brand-50' : 'bg-amber-50'">
                    <i class="fas fa-scale-balanced text-sm"
                       :class="netBalance >= 0 ? 'text-brand-500' : 'text-amber-500'"></i>
                </div>
            </div>
            <div class="text-xl font-bold"
                 :class="netBalance >= 0 ? 'text-brand-600' : 'text-amber-600'"
                 x-text="formatMoney(netBalance) + ' ₺'"></div>
            <div class="text-xs text-gray-400 mt-1">Gelir − Gider</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-lg shadow-gray-100/50">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-500 text-xs font-medium">Bu Ay Net</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center">
                    <i class="fas fa-calendar-check text-purple-500 text-sm"></i>
                </div>
            </div>
            <div class="text-xl font-bold"
                 :class="thisMonthNet >= 0 ? 'text-purple-600' : 'text-red-500'"
                 x-text="formatMoney(thisMonthNet) + ' ₺'"></div>
            <div class="text-xs text-gray-400 mt-1">{{ now()->locale('tr')->isoFormat('MMMM YYYY') }}</div>
        </div>
    </div>

    {{-- ── Sekme & Filtreler ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-lg shadow-gray-100/50 p-4">
        <div class="flex gap-1 mb-4 bg-gray-100 rounded-xl p-1 w-fit">
            <button @click="activeTab = 'income'"
                    :class="activeTab === 'income' ? 'bg-white shadow text-emerald-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-1.5 rounded-lg text-sm transition-all duration-200">
                <i class="fas fa-arrow-up mr-1.5"></i>Gelirler
                <span class="ml-1 text-xs bg-emerald-100 text-emerald-600 px-1.5 py-0.5 rounded-full" x-text="incomesList.length"></span>
            </button>
            <button @click="activeTab = 'expense'"
                    :class="activeTab === 'expense' ? 'bg-white shadow text-red-500 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-1.5 rounded-lg text-sm transition-all duration-200">
                <i class="fas fa-arrow-down mr-1.5"></i>Giderler
                <span class="ml-1 text-xs bg-red-100 text-red-500 px-1.5 py-0.5 rounded-full" x-text="expensesList.length"></span>
            </button>
            <button @click="activeTab = 'types'"
                    :class="activeTab === 'types' ? 'bg-white shadow text-brand-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-1.5 rounded-lg text-sm transition-all duration-200">
                <i class="fas fa-tags mr-1.5"></i>Türler
            </button>
        </div>

        <div x-show="activeTab !== 'types'" class="mb-4">
            <form method="GET" action="{{ route('pos.income-expense') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" :value="activeTab === 'expense' ? 'expense' : 'income'">
                <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2">
                    <i class="fas fa-calendar-alt text-gray-400 text-xs"></i>
                    <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                           class="bg-transparent text-sm text-gray-800 focus:outline-none w-32">
                    <span class="text-gray-400 text-xs">—</span>
                    <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                           class="bg-transparent text-sm text-gray-800 focus:outline-none w-32">
                </div>
                <button type="submit" class="px-3 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-medium transition-colors">
                    <i class="fas fa-filter mr-1"></i> Filtrele
                </button>
            </form>
        </div>

        {{-- ── GELİRLER LİSTESİ ── --}}
        <div x-show="activeTab === 'income'">
            <div x-show="incomesList.length > 0">
                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tür</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Açıklama</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ödeme</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tarih</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="item in incomesList" :key="item.id">
                                <tr class="hover:bg-emerald-50/30 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium"
                                              x-text="item.type?.name || item.type_name || '—'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate" x-text="item.note || '—'"></td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs text-gray-500" x-text="paymentLabel(item.payment_type)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-emerald-600" x-text="formatMoney(item.amount) + ' ₺'"></td>
                                    <td class="px-4 py-3 text-gray-500 text-xs" x-text="formatDate(item.date)"></td>
                                    <td class="px-4 py-3 text-right">
                                        <button @click="deleteRecord('income', item.id)" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div x-show="incomesList.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-400">
                <i class="fas fa-arrow-trend-up text-4xl mb-3 text-emerald-200"></i>
                <p class="text-sm">Seçili dönemde gelir kaydı yok</p>
                <button @click="openModal('income')" class="mt-3 px-4 py-2 bg-emerald-500 text-white rounded-xl text-sm font-medium hover:bg-emerald-600 transition-colors">
                    <i class="fas fa-plus mr-1"></i> İlk geliri ekle
                </button>
            </div>
        </div>

        {{-- ── GİDERLER LİSTESİ ── --}}
        <div x-show="activeTab === 'expense'">
            <div x-show="expensesList.length > 0">
                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tür</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Açıklama</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ödeme</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Tutar</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tarih</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="item in expensesList" :key="item.id">
                                <tr class="hover:bg-red-50/30 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 bg-red-100 text-red-600 rounded-full text-xs font-medium"
                                              x-text="item.type?.name || item.type_name || '—'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate" x-text="item.note || '—'"></td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs text-gray-500" x-text="paymentLabel(item.payment_type)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-red-500" x-text="formatMoney(item.amount) + ' ₺'"></td>
                                    <td class="px-4 py-3 text-gray-500 text-xs" x-text="formatDate(item.date)"></td>
                                    <td class="px-4 py-3 text-right">
                                        <button @click="deleteRecord('expense', item.id)" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div x-show="expensesList.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-400">
                <i class="fas fa-arrow-trend-down text-4xl mb-3 text-red-200"></i>
                <p class="text-sm">Seçili dönemde gider kaydı yok</p>
                <button @click="openModal('expense')" class="mt-3 px-4 py-2 bg-red-500 text-white rounded-xl text-sm font-medium hover:bg-red-600 transition-colors">
                    <i class="fas fa-plus mr-1"></i> İlk gideri ekle
                </button>
            </div>
        </div>

        {{-- ── TÜRLER LİSTESİ ── --}}
        <div x-show="activeTab === 'types'">
            <div class="flex justify-end mb-3">
                <button @click="openTypeModal()"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-white
                               bg-gradient-to-r from-brand-500 to-purple-600
                               shadow-lg shadow-brand-500/20 hover:scale-105 transition-all">
                    <i class="fas fa-plus mr-1.5"></i> Tür Ekle
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fas fa-arrow-up"></i> Gelir Türleri
                    </h3>
                    <div class="space-y-1.5">
                        <template x-for="type in incomeTypes" :key="type.id">
                            <div class="flex items-center justify-between px-3 py-2.5 bg-emerald-50 rounded-xl border border-emerald-100">
                                <span class="text-sm font-medium text-gray-700" x-text="type.name"></span>
                                <button @click="deleteType(type.id)" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </template>
                        <p x-show="incomeTypes.length === 0" class="text-sm text-gray-400 text-center py-4">Gelir türü yok</p>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fas fa-arrow-down"></i> Gider Türleri
                    </h3>
                    <div class="space-y-1.5">
                        <template x-for="type in expenseTypes" :key="type.id">
                            <div class="flex items-center justify-between px-3 py-2.5 bg-red-50 rounded-xl border border-red-100">
                                <span class="text-sm font-medium text-gray-700" x-text="type.name"></span>
                                <button @click="deleteType(type.id)" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </template>
                        <p x-show="expenseTypes.length === 0" class="text-sm text-gray-400 text-center py-4">Gider türü yok</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── GELİR / GİDER MODAL ── --}}
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="showModal = false"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             :class="modalType === 'income' ? 'bg-emerald-100' : 'bg-red-100'">
                            <i class="fas text-lg"
                               :class="modalType === 'income' ? 'fa-arrow-trend-up text-emerald-500' : 'fa-arrow-trend-down text-red-500'"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900" x-text="modalType === 'income' ? 'Gelir Ekle' : 'Gider Ekle'"></h3>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tür <span class="text-red-500">*</span></label>
                        <select x-model="form.income_expense_type_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                            <option value="">— Seçiniz —</option>
                            <template x-if="modalType === 'income'">
                                <template x-for="type in incomeTypes" :key="type.id">
                                    <option :value="type.id" x-text="type.name"></option>
                                </template>
                            </template>
                            <template x-if="modalType === 'expense'">
                                <template x-for="type in expenseTypes" :key="type.id">
                                    <option :value="type.id" x-text="type.name"></option>
                                </template>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tutar (₺) <span class="text-red-500">*</span></label>
                        <input type="number" x-model="form.amount" step="0.01" min="0.01" placeholder="0,00"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Ödeme Yöntemi</label>
                        <select x-model="form.payment_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                            <option value="cash">Nakit</option>
                            <option value="card">Kart</option>
                            <option value="transfer">Havale / EFT</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tarih <span class="text-red-500">*</span></label>
                        <input type="date" x-model="form.date"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Açıklama</label>
                        <textarea x-model="form.note" rows="2" placeholder="İsteğe bağlı not..."
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all resize-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="showModal = false"
                            class="flex-1 px-4 py-2.5 rounded-xl border-2 border-gray-200 text-sm font-semibold text-gray-600 hover:border-gray-300 hover:bg-gray-50 transition-all">
                        İptal
                    </button>
                    <button @click="submitRecord()" :disabled="saving"
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                            :class="modalType === 'income'
                                ? 'bg-gradient-to-r from-emerald-500 to-teal-500 shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/50'
                                : 'bg-gradient-to-r from-red-500 to-rose-500 shadow-lg shadow-red-500/25 hover:shadow-red-500/50'">
                        <span x-show="!saving" x-text="modalType === 'income' ? 'Gelir Kaydet' : 'Gider Kaydet'"></span>
                        <span x-show="saving"><i class="fas fa-spinner fa-spin mr-1"></i> Kaydediliyor...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── TÜR EKLEME MODAL ── --}}
    <div x-show="showTypeModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="showTypeModal = false"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-sm"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-tag mr-2 text-brand-500"></i>Yeni Tür Ekle</h3>
                    <button @click="showTypeModal = false" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times"></i></button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tür Adı <span class="text-red-500">*</span></label>
                        <input type="text" x-model="typeForm.name" placeholder="örn. Kira Geliri"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Yön</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button @click="typeForm.direction = 'income'"
                                    :class="typeForm.direction === 'income' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500'"
                                    class="py-2.5 border-2 rounded-xl text-sm font-medium transition-all">
                                <i class="fas fa-arrow-up mr-1.5"></i>Gelir
                            </button>
                            <button @click="typeForm.direction = 'expense'"
                                    :class="typeForm.direction === 'expense' ? 'border-red-500 bg-red-50 text-red-600' : 'border-gray-200 text-gray-500'"
                                    class="py-2.5 border-2 rounded-xl text-sm font-medium transition-all">
                                <i class="fas fa-arrow-down mr-1.5"></i>Gider
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="showTypeModal = false"
                            class="flex-1 px-4 py-2.5 rounded-xl border-2 border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all">
                        İptal
                    </button>
                    <button @click="submitType()"
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white
                                   bg-gradient-to-r from-brand-500 to-purple-600
                                   shadow-lg shadow-brand-500/20 hover:scale-[1.02] transition-all">
                        Tür Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function incomeExpenseApp() {
    return {
        activeTab: '{{ $tab }}',
        showModal: false,
        showTypeModal: false,
        saving: false,
        modalType: 'income',

        incomeTypes: @json($incomeTypes),
        expenseTypes: @json($expenseTypes),

        incomesList: @json($incomes->items()),
        expensesList: @json($expenses->items()),

        stats: {
            totalIncome: {{ $totalIncome ?? 0 }},
            totalExpense: {{ $totalExpense ?? 0 }},
            thisMonthIncome: {{ $thisMonthIncome ?? 0 }},
            thisMonthExpense: {{ $thisMonthExpense ?? 0 }},
        },

        get netBalance() { return this.stats.totalIncome - this.stats.totalExpense; },
        get thisMonthNet() { return this.stats.thisMonthIncome - this.stats.thisMonthExpense; },

        form: {
            income_expense_type_id: '',
            amount: '',
            payment_type: 'cash',
            date: new Date().toISOString().slice(0, 10),
            note: '',
        },

        typeForm: { name: '', direction: 'income' },

        init() {},

        paymentLabel(type) {
            return { cash: 'Nakit', card: 'Kart', transfer: 'Havale' }[type] || type || '—';
        },

        formatMoney(val) {
            return parseFloat(val || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatDate(d) {
            if (!d) return '—';
            const parts = d.split('-');
            if (parts.length === 3) return parts[2] + '.' + parts[1] + '.' + parts[0];
            try { return new Date(d).toLocaleDateString('tr-TR'); } catch(e) { return d; }
        },

        isInCurrentMonth(dateStr) {
            const now = new Date();
            const d = new Date(dateStr);
            return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
        },

        openModal(type) {
            this.modalType = type;
            this.form = {
                income_expense_type_id: '',
                amount: '',
                payment_type: 'cash',
                date: new Date().toISOString().slice(0, 10),
                note: '',
            };
            this.showModal = true;
        },

        openTypeModal() {
            this.typeForm = { name: '', direction: 'income' };
            this.showTypeModal = true;
        },

        async submitRecord() {
            if (!this.form.income_expense_type_id || !this.form.amount || !this.form.date) {
                showToast('Lütfen zorunlu alanları doldurun', 'error');
                return;
            }
            this.saving = true;
            try {
                const url = this.modalType === 'income'
                    ? '{{ route("pos.income-expense.income.store") }}'
                    : '{{ route("pos.income-expense.expense.store") }}';
                const data = await posAjax(url, this.form, 'POST');
                const amount = parseFloat(this.form.amount);

                if (this.modalType === 'income') {
                    this.incomesList.unshift(data.income);
                    this.stats.totalIncome += amount;
                    if (this.isInCurrentMonth(this.form.date)) this.stats.thisMonthIncome += amount;
                    this.activeTab = 'income';
                } else {
                    this.expensesList.unshift(data.expense);
                    this.stats.totalExpense += amount;
                    if (this.isInCurrentMonth(this.form.date)) this.stats.thisMonthExpense += amount;
                    this.activeTab = 'expense';
                }

                showToast(this.modalType === 'income' ? 'Gelir kaydedildi ✓' : 'Gider kaydedildi ✓', 'success');
                this.showModal = false;
            } catch (e) {
                showToast(e.message || 'Bir hata oluştu', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteRecord(type, id) {
            if (!confirm('Bu kaydı silmek istediğinize emin misiniz?')) return;
            try {
                const url = type === 'income'
                    ? '{{ url("income-expense/income") }}/' + id
                    : '{{ url("income-expense/expense") }}/' + id;
                await posAjax(url, {}, 'DELETE');

                if (type === 'income') {
                    const item = this.incomesList.find(i => i.id === id);
                    if (item) {
                        this.stats.totalIncome -= parseFloat(item.amount);
                        if (this.isInCurrentMonth(item.date)) this.stats.thisMonthIncome -= parseFloat(item.amount);
                    }
                    this.incomesList = this.incomesList.filter(i => i.id !== id);
                } else {
                    const item = this.expensesList.find(i => i.id === id);
                    if (item) {
                        this.stats.totalExpense -= parseFloat(item.amount);
                        if (this.isInCurrentMonth(item.date)) this.stats.thisMonthExpense -= parseFloat(item.amount);
                    }
                    this.expensesList = this.expensesList.filter(i => i.id !== id);
                }
                showToast('Kayıt silindi ✓', 'success');
            } catch (e) {
                showToast(e.message || 'Silme işlemi başarısız', 'error');
            }
        },

        async submitType() {
            if (!this.typeForm.name) { showToast('Tür adı zorunlu', 'error'); return; }
            try {
                const res = await posAjax('{{ route("pos.income-expense.type.store") }}', this.typeForm, 'POST');
                showToast('Tür eklendi ✓', 'success');
                this.showTypeModal = false;
                if (this.typeForm.direction === 'income') {
                    this.incomeTypes.push(res.type);
                } else {
                    this.expenseTypes.push(res.type);
                }
            } catch (e) {
                showToast(e.message || 'Bir hata oluştu', 'error');
            }
        },

        async deleteType(id) {
            if (!confirm('Bu türü silmek istediğinize emin misiniz?')) return;
            try {
                await posAjax('{{ url("income-expense/type") }}/' + id, {}, 'DELETE');
                showToast('Tür silindi ✓', 'success');
                this.incomeTypes = this.incomeTypes.filter(t => t.id !== id);
                this.expenseTypes = this.expenseTypes.filter(t => t.id !== id);
            } catch (e) {
                showToast(e.message || 'Silme işlemi başarısız', 'error');
            }
        },
    };
}
</script>
@endpush
@endsection
