@extends('pos.layouts.app')

@section('title', 'Ayarlar')

@section('content')
<div x-data="{ activeTab: 'branch' }" class="p-3 sm:p-6 overflow-y-auto h-full">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Ayarlar</h1>

    <div class="mb-6 rounded-2xl border border-sky-200 bg-gradient-to-r from-sky-50 via-white to-cyan-50 p-4 sm:p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Sunucu Zamanı</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $sunucuTarihiSaati->format('d.m.Y H:i:s') }}</div>
                <div class="mt-1 text-sm text-gray-600">Aktif zaman dilimi: <span class="font-semibold text-gray-800">{{ $aktifSaatDilimi }}</span></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm min-w-0 lg:min-w-[360px]">
                <div class="rounded-xl border border-sky-100 bg-white/90 px-4 py-3">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Durum</div>
                    <div class="mt-1 font-semibold text-emerald-700">Saat senkronize görünüyor</div>
                </div>
                <div class="rounded-xl border border-sky-100 bg-white/90 px-4 py-3">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Not</div>
                    <div class="mt-1 text-gray-700">Bu alan sunucudan okunur. Sistem saatini değil, uygulamanın gördüğü zamanı gösterir.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-100 pb-3 overflow-x-auto hide-scrollbar">
        <button @click="activeTab = 'branch'" :class="activeTab === 'branch' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-store mr-1"></i> Şube Bilgileri
        </button>
        <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <i class="fas fa-cog mr-1"></i> Genel Ayarlar
        </button>
        @if($canManagePaymentTypes)
            <button @click="activeTab = 'payment'" :class="activeTab === 'payment' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-credit-card mr-1"></i> Ödeme Tipleri
            </button>
        @endif
        <button @click="activeTab = 'tax'" :class="activeTab === 'tax' ? 'bg-gradient-to-r from-brand-500 to-purple-600 text-white' : 'bg-white text-gray-500 hover:text-gray-800'" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
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
                    <input type="text" name="name" value="{{ $branch->name ?? '' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Telefon</label>
                    <input type="text" name="phone" value="{{ $branch->phone ?? '' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-500 mb-1">Adres</label>
                    <textarea name="address" rows="2" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $branch->address ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Şehir</label>
                    <input type="text" name="city" value="{{ $branch->city ?? '' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">İlçe</label>
                    <input type="text" name="district" value="{{ $branch->district ?? '' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Saat Dilimi</label>
                    @php
                        $selectedTimezone = $branch->settings['timezone'] ?? 'Europe/Istanbul';
                        $timezoneOptions = [
                            'Europe/Istanbul' => 'Türkiye (Europe/Istanbul)',
                            'Europe/Berlin' => 'Avrupa (Europe/Berlin)',
                            'Europe/London' => 'İngiltere (Europe/London)',
                            'America/New_York' => 'ABD (America/New_York)',
                            'Asia/Dubai' => 'Dubai (Asia/Dubai)',
                            'Asia/Baku' => 'Bakü (Asia/Baku)',
                        ];
                    @endphp
                    <select name="timezone" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                        @foreach($timezoneOptions as $tzValue => $tzLabel)
                            <option value="{{ $tzValue }}" {{ $selectedTimezone === $tzValue ? 'selected' : '' }}>{{ $tzLabel }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Rapor ve kasa saatleri bu zaman dilimine göre hesaplanır.</p>
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Kaydet
                </button>
            </div>
        </form>
    </div>

    {{-- General Settings Tab --}}
    @php
        $receiptDesignerState = [
            'receipt_business_title' => $tenant->meta['receipt_business_title'] ?? config('app.name', 'EMARE POS'),
            'receipt_header' => $tenant->meta['receipt_header'] ?? '',
            'receipt_footer' => $tenant->meta['receipt_footer'] ?? '',
            'receipt_paper_width' => (string) ($tenant->meta['receipt_paper_width'] ?? '80'),
            'receipt_font_size' => (int) ($tenant->meta['receipt_font_size'] ?? 12),
            'receipt_show_datetime' => (bool) ($tenant->meta['receipt_show_datetime'] ?? true),
            'receipt_show_receipt_no' => (bool) ($tenant->meta['receipt_show_receipt_no'] ?? true),
            'receipt_show_customer_name' => (bool) ($tenant->meta['receipt_show_customer_name'] ?? true),
            'receipt_show_customer_balance' => (bool) ($tenant->meta['receipt_show_customer_balance'] ?? false),
            'receipt_show_staff_name' => (bool) ($tenant->meta['receipt_show_staff_name'] ?? true),
            'receipt_show_payment_breakdown' => (bool) ($tenant->meta['receipt_show_payment_breakdown'] ?? true),
            'receipt_show_tax_breakdown' => (bool) ($tenant->meta['receipt_show_tax_breakdown'] ?? false),
            'receipt_show_service_fee' => (bool) ($tenant->meta['receipt_show_service_fee'] ?? true),
            'receipt_show_notes' => (bool) ($tenant->meta['receipt_show_notes'] ?? true),
        ];
    @endphp
    <div x-show="activeTab === 'general'" x-transition x-data='receiptDesigner(@json($receiptDesignerState))'>
        <form method="POST" action="{{ url('/settings/general') }}" class="bg-white rounded-xl border border-gray-100 p-6 space-y-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Fiş Üst Yazı</label>
                    <textarea x-model="form.receipt_header" name="receipt_header" rows="3" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $tenant->meta['receipt_header'] ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Fiş Alt Yazı</label>
                    <textarea x-model="form.receipt_footer" name="receipt_footer" rows="3" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">{{ $tenant->meta['receipt_footer'] ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Para Birimi Sembolü</label>
                    <input type="text" name="currency_symbol" value="{{ $tenant->meta['currency_symbol'] ?? '₺' }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Hizmet Bedeli Yüzdesi</label>
                    <div class="relative">
                        <input type="number" name="service_fee_percentage" min="0" max="100" step="0.01" value="{{ $tenant->meta['service_fee_percentage'] ?? 0 }}" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 pr-10 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">%</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Hızlı satış ekranındaki Diğer menüsünden açılan hizmet bedeli bu orana göre toplama eklenir.</p>
                </div>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 sm:p-5 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-amber-900">İşletme Tarihi / Saati Override</h3>
                        <p class="text-xs text-amber-800/80 mt-1">Vardiya kapanışı, gece devri veya test senaryoları için uygulamanın kullanacağı işletme tarihini ve saatini ayrıca kaydedebilirsiniz.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-amber-900 cursor-pointer">
                        <input type="checkbox" name="isletme_saati_override_aktif" value="1" {{ ($tenant->meta['isletme_saati_override_aktif'] ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded bg-white border-amber-300 text-amber-600 focus:ring-amber-500/20">
                        Aktif
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-amber-900 mb-1">İşletme Tarihi</label>
                        <input type="date" name="isletme_tarihi_override" value="{{ $tenant->meta['isletme_tarihi_override'] ?? '' }}" class="w-full bg-white border border-amber-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500/20 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm text-amber-900 mb-1">İşletme Saati</label>
                        <input type="time" name="isletme_saati_override" value="{{ $tenant->meta['isletme_saati_override'] ?? '' }}" class="w-full bg-white border border-amber-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500/20 focus:border-transparent">
                    </div>
                </div>

                <div class="rounded-xl border border-amber-100 bg-white/85 px-4 py-3 text-xs text-amber-900/80">
                    Bu adım sadece ayar alanını hazırlar. Sonraki adımda satışlar, fişler ve raporlar bu override değerini kullanacak şekilde bağlanacaktır.
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 space-y-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Fiş Tasarım Aracı</h3>
                        <p class="text-xs text-gray-500 mt-1">Fişte hangi alanların görüneceğini seçin, kağıt genişliğini belirleyin ve önizlemeyi anında görün.</p>
                    </div>
                    <div class="rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-[11px] font-medium text-amber-700">
                        Canlı önizleme aktif
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] gap-6 items-start">
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm text-gray-500 mb-1">Fiş Başlığı</label>
                                <input type="text" x-model="form.receipt_business_title" name="receipt_business_title" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-500 mb-1">Kağıt Genişliği</label>
                                <select x-model="form.receipt_paper_width" name="receipt_paper_width" class="w-full bg-white border border-gray-200 text-gray-900 text-sm rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-transparent">
                                    <option value="58">58 mm</option>
                                    <option value="80">80 mm</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-500 mb-1">Yazı Boyutu</label>
                            <div class="flex items-center gap-3">
                                <input type="range" x-model="form.receipt_font_size" name="receipt_font_size" min="10" max="16" step="1" class="w-full accent-brand-500">
                                <span class="w-12 text-right text-sm font-semibold text-gray-700" x-text="form.receipt_font_size + ' px'"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40">
                                <span>Tarih ve saat</span>
                                <input x-model="form.receipt_show_datetime" type="checkbox" name="receipt_show_datetime" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40">
                                <span>Fiş numarası</span>
                                <input x-model="form.receipt_show_receipt_no" type="checkbox" name="receipt_show_receipt_no" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40">
                                <span>Müşteri adı</span>
                                <input x-model="form.receipt_show_customer_name" type="checkbox" name="receipt_show_customer_name" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40">
                                <span>Müşteri borç/bakiye</span>
                                <input x-model="form.receipt_show_customer_balance" type="checkbox" name="receipt_show_customer_balance" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40">
                                <span>Kasiyer adı</span>
                                <input x-model="form.receipt_show_staff_name" type="checkbox" name="receipt_show_staff_name" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40">
                                <span>Ödeme kırılımı</span>
                                <input x-model="form.receipt_show_payment_breakdown" type="checkbox" name="receipt_show_payment_breakdown" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40">
                                <span>KDV kırılımı</span>
                                <input x-model="form.receipt_show_tax_breakdown" type="checkbox" name="receipt_show_tax_breakdown" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40">
                                <span>Hizmet bedeli</span>
                                <input x-model="form.receipt_show_service_fee" type="checkbox" name="receipt_show_service_fee" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                            <label class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-700 cursor-pointer hover:border-brand-200 hover:bg-brand-50/40 md:col-span-2">
                                <span>Satış notu</span>
                                <input x-model="form.receipt_show_notes" type="checkbox" name="receipt_show_notes" value="1" class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-gradient-to-br from-slate-50 to-white p-4 lg:p-5 shadow-sm sticky top-4">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800">Fiş Önizleme</h4>
                                <p class="text-xs text-gray-500 mt-1">Örnek satış üzerinden canlı görünüm.</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] text-gray-600" x-text="form.receipt_paper_width + ' mm'"></span>
                        </div>

                        <div class="mx-auto rounded-lg border border-dashed border-gray-300 bg-white px-4 py-5 text-gray-900 shadow-inner"
                             :style="previewStyle()">
                            <div class="text-center font-bold tracking-wide" x-text="form.receipt_business_title || 'EMARE POS'"></div>
                            <div x-show="form.receipt_header" class="mt-1 text-center whitespace-pre-line text-[0.9em] text-gray-600" x-text="form.receipt_header"></div>
                            <div x-show="form.receipt_show_datetime" class="mt-2 text-center text-[0.88em] text-gray-500">13.03.2026 14:35</div>
                            <div x-show="form.receipt_show_receipt_no" class="text-center text-[0.88em] text-gray-500">Fiş: S-20260313-0042</div>
                            <div class="my-3 border-t border-dashed border-gray-300"></div>
                            <div x-show="form.receipt_show_customer_name" class="flex justify-between gap-3 text-[0.92em]">
                                <span class="text-gray-500">Müşteri</span>
                                <span class="font-medium">Ahmet Kaya</span>
                            </div>
                            <div x-show="form.receipt_show_customer_balance" class="flex justify-between gap-3 text-[0.92em] mt-1">
                                <span class="text-gray-500">Borç Durumu</span>
                                <span class="font-medium text-red-600">Borç: 245,75 TL</span>
                            </div>
                            <div x-show="form.receipt_show_staff_name" class="flex justify-between gap-3 text-[0.92em] mt-1">
                                <span class="text-gray-500">Kasiyer</span>
                                <span class="font-medium">Emare Kasiyer</span>
                            </div>
                            <div class="my-3 border-t border-dashed border-gray-300"></div>
                            <div class="space-y-1.5 text-[0.92em]">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium">Adana Kebap</div>
                                        <div class="text-[0.85em] text-gray-500">1 x 320,00 TL</div>
                                    </div>
                                    <div class="font-medium">320,00 TL</div>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium">Ayran</div>
                                        <div class="text-[0.85em] text-gray-500">2 x 35,00 TL</div>
                                    </div>
                                    <div class="font-medium">70,00 TL</div>
                                </div>
                            </div>
                            <div class="my-3 border-t border-dashed border-gray-300"></div>
                            <div x-show="form.receipt_show_tax_breakdown" class="flex justify-between gap-3 text-[0.92em] text-gray-600">
                                <span>KDV</span>
                                <span>35,45 TL</span>
                            </div>
                            <div x-show="form.receipt_show_service_fee" class="flex justify-between gap-3 text-[0.92em] text-gray-600 mt-1">
                                <span>Hizmet Bedeli</span>
                                <span>19,50 TL</span>
                            </div>
                            <div class="mt-2 flex justify-between gap-3 text-[1em] font-bold">
                                <span>TOPLAM</span>
                                <span>409,50 TL</span>
                            </div>
                            <template x-if="form.receipt_show_payment_breakdown">
                                <div class="mt-3 space-y-1 text-[0.9em] text-gray-600">
                                    <div class="flex justify-between gap-3"><span>Nakit</span><span>200,00 TL</span></div>
                                    <div class="flex justify-between gap-3"><span>Kart</span><span>209,50 TL</span></div>
                                </div>
                            </template>
                            <div x-show="form.receipt_show_notes" class="mt-3 rounded-md bg-amber-50 px-3 py-2 text-[0.88em] text-amber-800">
                                Not: Acısız hazırlansın, müşteri bakiyesi fişte gösterilsin.
                            </div>
                            <div x-show="form.receipt_footer" class="mt-4 text-center whitespace-pre-line text-[0.9em] text-gray-600" x-text="form.receipt_footer"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 space-y-4">
                <h3 class="text-sm font-medium text-gray-700">Seçenekler</h3>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="tax_included" value="1" {{ ($tenant->meta['tax_included'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                    Fiyatlara KDV dahil
                </label>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="auto_print_receipt" value="1" {{ ($tenant->meta['auto_print_receipt'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                    Satış sonrası otomatik fiş yazdır
                </label>
                <label class="flex items-center gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="kitchen_print" value="1" {{ ($tenant->meta['kitchen_print'] ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded bg-white border-gray-200 text-blue-600 focus:ring-brand-500/20">
                    Mutfak yazıcısı aktif
                </label>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-gradient-to-r from-brand-500 to-purple-600 hover:shadow-lg hover:shadow-brand-200 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    <i class="fas fa-save mr-1"></i> Kaydet
                </button>
            </div>
        </form>
    </div>

    {{-- Payment Types Tab --}}
    <div x-show="activeTab === 'payment'" x-transition x-data="paymentTypeManager()">
        @if(!$canManagePaymentTypes)
            <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-xl p-4">
                Bu alan yalnızca merkez şube ve yetkili kullanıcılar tarafından yönetilebilir.
            </div>
        @endif
        {{-- Yeni Ödeme Türü Ekle --}}
        <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4">
            <h4 class="text-sm font-semibold text-gray-800 mb-3"><i class="fas fa-plus-circle text-brand-500 mr-1"></i> Yeni Ödeme Türü Ekle</h4>
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ödeme Türü Adı *</label>
                    <input type="text" x-model="newType.name" @keydown.enter="addType()"
                           placeholder="Örn: Yemek Kartı, EFT POS, Online Ödeme..."
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <div class="w-40">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kod (opsiyonel)</label>
                    <input type="text" x-model="newType.code" @keydown.enter="addType()"
                           placeholder="Örn: yemek_karti"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>
                <button @click="addType()" :disabled="!newType.name.trim() || saving"
                        class="px-5 py-2 bg-gradient-to-r from-brand-500 to-purple-600 text-white text-sm font-medium rounded-lg hover:opacity-90 disabled:opacity-50 transition-all whitespace-nowrap">
                    <i class="fas fa-plus mr-1"></i> Ekle
                </button>
            </div>
            {{-- Hızlı Ekleme Önerileri --}}
            <div class="flex gap-2 mt-3 flex-wrap">
                <template x-for="preset in presets" :key="preset">
                    <button @click="newType.name = preset" class="px-3 py-1 bg-gray-100 hover:bg-brand-50 text-gray-600 hover:text-brand-600 text-xs rounded-lg border border-gray-200 hover:border-brand-200 transition-colors" x-text="preset"></button>
                </template>
            </div>
        </div>

        {{-- Mevcut Ödeme Türleri --}}
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3.5">Ad</th>
                            <th class="px-4 py-3.5">Kod</th>
                            <th class="px-4 py-3.5 text-center">Sıra</th>
                            <th class="px-4 py-3.5 text-center">Durum</th>
                            <th class="px-4 py-3.5 text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="pt in types" :key="pt.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <template x-if="editingId !== pt.id">
                                        <span class="text-gray-900 font-medium" x-text="pt.name"></span>
                                    </template>
                                    <template x-if="editingId === pt.id">
                                        <input type="text" x-model="editForm.name" class="w-full px-2 py-1 border border-brand-300 rounded text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-400">
                                    </template>
                                </td>
                                <td class="px-4 py-3">
                                    <template x-if="editingId !== pt.id">
                                        <span class="font-mono text-gray-500" x-text="pt.code || '-'"></span>
                                    </template>
                                    <template x-if="editingId === pt.id">
                                        <input type="text" x-model="editForm.code" class="w-full px-2 py-1 border border-brand-300 rounded text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-400">
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs text-gray-500" x-text="pt.sort_order"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="toggleActive(pt)"
                                            class="text-xs px-2.5 py-1 rounded-full border transition-colors"
                                            :class="pt.is_active ? 'bg-green-500/10 text-emerald-600 border-green-500/30 hover:bg-red-50 hover:text-red-500 hover:border-red-300' : 'bg-red-500/10 text-red-500 border-red-500/30 hover:bg-green-50 hover:text-emerald-600 hover:border-green-300'"
                                            x-text="pt.is_active ? 'Aktif' : 'Pasif'"></button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <template x-if="editingId !== pt.id">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="startEdit(pt)" class="text-brand-500 hover:text-brand-700 text-xs"><i class="fas fa-edit"></i></button>
                                            <button @click="deleteType(pt)" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </template>
                                    <template x-if="editingId === pt.id">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="saveEdit(pt)" class="text-emerald-500 hover:text-emerald-700 text-xs font-medium"><i class="fas fa-check mr-0.5"></i>Kaydet</button>
                                            <button @click="editingId = null" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fas fa-times"></i></button>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="types.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Ödeme türü bulunamadı. Yukarıdan ekleyin.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function receiptDesigner(initial) {
        return {
            form: {
                ...initial,
                receipt_paper_width: String(initial.receipt_paper_width || '80'),
                receipt_font_size: Number(initial.receipt_font_size || 12),
            },

            previewStyle() {
                const width = this.form.receipt_paper_width === '58' ? 250 : 320;
                return `width:${width}px;font-size:${this.form.receipt_font_size}px;line-height:1.45`;
            },
        };
    }

    function paymentTypeManager() {
        return {
            types: @json($paymentTypes),
            newType: { name: '', code: '' },
            saving: false,
            editingId: null,
            editForm: { name: '', code: '' },
            presets: ['Havale', 'Yemek Kartı', 'EFT POS', 'Seyyar POS', 'Online Ödeme', 'Çek', 'Senet', 'Mobil Ödeme'],

            async addType() {
                if (!this.newType.name.trim()) return;
                this.saving = true;
                try {
                    const data = await posAjax('{{ route("pos.payment-types.store") }}', this.newType);
                    if (data.success) {
                        this.types.push(data.paymentType);
                        this.newType = { name: '', code: '' };
                        showToast('Ödeme türü eklendi!', 'success');
                    }
                } catch(e) { showToast(e.message || 'Eklenemedi', 'error'); }
                this.saving = false;
            },

            startEdit(pt) {
                this.editingId = pt.id;
                this.editForm = { name: pt.name, code: pt.code || '' };
            },

            async saveEdit(pt) {
                try {
                    const data = await posAjax('/payment-types/' + pt.id, this.editForm, 'PUT');
                    if (data.success) {
                        pt.name = data.paymentType.name;
                        pt.code = data.paymentType.code;
                        this.editingId = null;
                        showToast('Güncellendi!', 'success');
                    }
                } catch(e) { showToast(e.message || 'Güncellenemedi', 'error'); }
            },

            async toggleActive(pt) {
                try {
                    const data = await posAjax('/payment-types/' + pt.id, { ...pt, is_active: !pt.is_active }, 'PUT');
                    if (data.success) {
                        pt.is_active = data.paymentType.is_active;
                        showToast(pt.is_active ? 'Aktifleştirildi' : 'Pasifleştirildi', 'success');
                    }
                } catch(e) { showToast(e.message || 'Güncellenemedi', 'error'); }
            },

            async deleteType(pt) {
                if (!confirm(pt.name + ' ödeme türünü silmek istediğinize emin misiniz?')) return;
                try {
                    const data = await posAjax('/payment-types/' + pt.id, {}, 'DELETE');
                    if (data.success) {
                        this.types = this.types.filter(t => t.id !== pt.id);
                        showToast('Silindi!', 'success');
                    }
                } catch(e) { showToast(e.message || 'Silinemedi', 'error'); }
            },
        };
    }
    </script>

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
