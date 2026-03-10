<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Income;
use App\Models\IncomeExpenseType;
use App\Models\AccountTransaction;
use App\Models\StockMovement;
use App\Models\CampaignUsage;
use App\Models\LoyaltyPoint;
use App\Services\PrinterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SaleService
{
    private function odemeDagiliminiDogrula(array $data, float $grandTotal): array
    {
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $cashAmount = (float) ($data['cash_amount'] ?? 0);
        $cardAmount = (float) ($data['card_amount'] ?? 0);
        $creditAmount = (float) ($data['credit_amount'] ?? 0);
        $transferAmount = (float) ($data['transfer_amount'] ?? 0);

        if ($paymentMethod === 'cash' && $cashAmount <= 0) {
            $cashAmount = $grandTotal;
        }

        if ($paymentMethod === 'card' && $cardAmount <= 0) {
            $cardAmount = $grandTotal;
        }

        if ($paymentMethod === 'transfer' && $transferAmount <= 0) {
            $transferAmount = $grandTotal;
        }

        if ($paymentMethod === 'credit' && $creditAmount <= 0) {
            $creditAmount = $grandTotal;
        }

        $toplamOdeme = round($cashAmount + $cardAmount + $creditAmount + $transferAmount, 2);
        $grandTotal = round($grandTotal, 2);

        if ($paymentMethod === 'mixed' && $toplamOdeme !== $grandTotal) {
            throw new \Exception('Karma ödeme toplamı satış tutarı ile eşleşmiyor.');
        }

        if (in_array($paymentMethod, ['cash', 'card', 'transfer', 'credit'], true) && $toplamOdeme !== $grandTotal) {
            throw new \Exception('Ödeme tutarı satış toplamı ile eşleşmiyor.');
        }

        if (($paymentMethod === 'credit' || $creditAmount > 0) && empty($data['customer_id'])) {
            throw new \Exception('Veresiye satış için müşteri seçmelisiniz.');
        }

        return [
            'cash_amount' => $cashAmount,
            'card_amount' => $cardAmount,
            'credit_amount' => $creditAmount,
            'transfer_amount' => $transferAmount,
        ];
    }

    /**
     * Create a new sale with items, update stock, handle payments
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $branchId = $data['branch_id'] ?? session('branch_id');
            $tenantId = $data['tenant_id'] ?? session('tenant_id');
            
            // Generate receipt number
            $receiptNo = $this->generateReceiptNo($branchId);
            
            // Calculate totals from items
            $items = $data['items'] ?? [];
            $calculated = $this->calculateTotals($items, $data['discount'] ?? 0);
            $paymentBreakdown = $this->odemeDagiliminiDogrula($data, (float) $calculated['grand_total']);
            
            // Create sale record
            $sale = Sale::create([
                'tenant_id' => $tenantId,
                'receipt_no' => $receiptNo,
                'branch_id' => $branchId,
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'total_items' => count($items),
                'subtotal' => $calculated['subtotal'],
                'vat_total' => $calculated['vat_total'],
                'additional_tax_total' => $calculated['additional_tax_total'],
                'discount_total' => $calculated['discount_total'],
                'grand_total' => $calculated['grand_total'],
                'discount' => $data['discount'] ?? 0,
                'cash_amount' => $paymentBreakdown['cash_amount'],
                'card_amount' => $paymentBreakdown['card_amount'],
                'credit_amount' => $paymentBreakdown['credit_amount'],
                'transfer_amount' => $paymentBreakdown['transfer_amount'],
                'status' => 'completed',
                'staff_name' => $data['staff_name'] ?? auth()->user()?->name,
                'application' => $data['application'] ?? 'pos',
                'notes' => $data['notes'] ?? null,
                'sold_at' => $data['sold_at'] ?? Carbon::now(),
            ]);
            
            // Create sale items and update stock (use calculated items for correct vat/totals)
            $calculatedItems = $calculated['items'];
            $productMap = []; // Ürünleri önbelleğe al — alttaki mutfak blokları tekrar sorgu yapmasın
            foreach ($calculatedItems as $item) {
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();
                if (! $product) {
                    throw new \Exception('Geçersiz ürün seçimi: #' . $item['product_id']);
                }

                $productMap[$product->id] = $product;

                if ($product && !$product->is_service) {
                    $qty = $item['quantity'] ?? 1;
                }
                
                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'] ?? $product?->name ?? 'Bilinmeyen Ürün',
                    'barcode' => $item['barcode'] ?? $product?->barcode,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? $product?->sale_price ?? 0,
                    'purchase_cost' => $product?->purchase_price ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'vat_rate' => $item['vat_rate'] ?? $product?->vat_rate ?? 20,
                    'vat_amount' => $item['vat_amount'] ?? 0,
                    'additional_taxes' => $item['additional_taxes'] ?? null,
                    'additional_tax_amount' => $item['additional_tax_amount'] ?? 0,
                    'total' => $item['total'] ?? 0,
                ]);
                
                // Update stock
                if ($product && !$product->is_service) {
                    $remainingStock = $product->adjustStockForBranch($branchId, -(float) ($item['quantity'] ?? 1), true);
                    
                    // Create stock movement
                    StockMovement::create([
                        'tenant_id' => $tenantId,
                        'branch_id' => $data['branch_id'] ?? session('branch_id'),
                        'type' => 'sale',
                        'barcode' => $product->barcode,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'transaction_code' => $receiptNo,
                        'quantity' => -($item['quantity'] ?? 1),
                        'remaining' => $remainingStock,
                        'unit_price' => $item['unit_price'] ?? $product->sale_price,
                        'total' => $item['total'] ?? 0,
                        'movement_date' => Carbon::now(),
                    ]);
                }
            }
            
            // Handle credit/veresiye payment - update customer balance
            $creditAmount = 0;
            if ($data['payment_method'] === 'credit') {
                $creditAmount = $calculated['grand_total'];
            } elseif ($data['payment_method'] === 'mixed' && !empty($paymentBreakdown['credit_amount'])) {
                $creditAmount = (float) $paymentBreakdown['credit_amount'];
            }

            if ($creditAmount > 0 && !empty($data['customer_id'])) {
                // lockForUpdate: eş zamanlı credit satışlarda balance_after tutarlılığı
                $customer = Customer::where('id', $data['customer_id'])->lockForUpdate()->first();
                if (! $customer) {
                    throw new \Exception('Geçersiz müşteri seçimi.');
                }

                $customer->decrement('balance', $creditAmount);
                
                AccountTransaction::create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customer->id,
                    'type' => 'sale',
                    'amount' => -$creditAmount,
                    'balance_after' => $customer->balance,
                    'description' => "Satış (Veresiye): {$receiptNo}",
                    'reference' => $receiptNo,
                    'transaction_date' => Carbon::now(),
                ]);
            }
            
            // Handle campaign usage
            if (!empty($data['campaign_id'])) {
                CampaignUsage::create([
                    'campaign_id' => $data['campaign_id'],
                    'customer_id' => $data['customer_id'] ?? null,
                    'sale_id' => $sale->id,
                    'discount_applied' => $calculated['discount_total'],
                ]);
            }
            
            // Handle loyalty points earning
            if (!empty($data['customer_id']) && !empty($data['loyalty_program_id'])) {
                $program = \App\Models\LoyaltyProgram::find($data['loyalty_program_id']);
                if ($program && $program->is_active) {
                    $earnedPoints = (int)floor($calculated['grand_total'] * $program->points_per_currency);
                    if ($earnedPoints > 0) {
                        $lastPoint = LoyaltyPoint::where('customer_id', $data['customer_id'])
                            ->where('loyalty_program_id', $program->id)
                            ->latest()
                            ->lockForUpdate()
                            ->first();
                        $currentBalance = $lastPoint ? $lastPoint->balance_after : 0;
                        
                        LoyaltyPoint::create([
                            'customer_id' => $data['customer_id'],
                            'loyalty_program_id' => $program->id,
                            'points' => $earnedPoints,
                            'type' => 'earn',
                            'description' => "Satış puanı: {$receiptNo}",
                            'sale_id' => $sale->id,
                            'balance_after' => $currentBalance + $earnedPoints,
                        ]);
                    }
                }
            }
            
            // Mutfak siparişi oluştur — hata olursa satışı etkileme
            try {
                if ($tenantId && $branchId) {
                    $kitchenItems = array_filter($calculated['items'], function ($item) use ($productMap) {
                        $p = $productMap[$item['product_id']] ?? null;
                        return !$p || !$p->is_service;
                    });
                    if (count($kitchenItems) > 0) {
                        $order = \App\Models\Order::create([
                            'tenant_id'      => $tenantId,
                            'branch_id'      => $branchId,
                            'sale_id'        => $sale->id,
                            'order_number'   => 'POS-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                            'user_id'        => $data['user_id'] ?? auth()->id(),
                            'customer_id'    => $data['customer_id'] ?? null,
                            'status'         => 'pending',
                            'order_type'     => 'takeaway',
                            'total_items'    => count($kitchenItems),
                            'subtotal'       => $calculated['subtotal'],
                            'vat_total'      => $calculated['vat_total'],
                            'discount_total' => $calculated['discount_total'],
                            'grand_total'    => $calculated['grand_total'],
                            'notes'          => $data['notes'] ?? null,
                            'ordered_at'     => Carbon::now(),
                        ]);
                        foreach ($kitchenItems as $item) {
                            $kProduct = $productMap[$item['product_id']] ?? null;
                            \App\Models\OrderItem::create([
                                'order_id'     => $order->id,
                                'product_id'   => $item['product_id'],
                                'product_name' => $item['product_name'] ?? $kProduct?->name ?? 'Bilinmeyen Ürün',
                                'quantity'     => $item['quantity'] ?? 1,
                                'unit_price'   => $item['unit_price'] ?? 0,
                                'discount'     => $item['discount'] ?? 0,
                                'vat_rate'     => $item['vat_rate'] ?? 20,
                                'vat_amount'   => $item['vat_amount'] ?? 0,
                                'total'        => $item['total'] ?? 0,
                                'status'       => 'pending',
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Mutfak siparişi oluşturulamadı: ' . $e->getMessage(), ['sale_id' => $sale->id]);
            }

            // Mutfak yazıcısına gönder (kitchen_print ayarı açıksa)
            try {
                $tenant = \App\Models\Tenant::find($tenantId);
                $kitchenPrintEnabled = $tenant?->meta['kitchen_print'] ?? false;
                if ($kitchenPrintEnabled) {
                    $kitchenData = [
                        'receipt_no' => $receiptNo,
                        'table_name' => $data['table_name'] ?? null,
                        'note' => $data['notes'] ?? null,
                        'items' => array_map(function ($item) use ($productMap) {
                            return [
                                'product_name' => $item['product_name'] ?? ($productMap[$item['product_id']] ?? null)?->name ?? '',
                                'quantity' => $item['quantity'] ?? 1,
                                'note' => $item['note'] ?? null,
                            ];
                        }, $items),
                    ];
                    PrinterService::printKitchenTicket($kitchenData);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Mutfak fişi yazdırılamadı: ' . $e->getMessage(), ['sale_id' => $sale->id]);
            }

            $this->satisGeliriOlustur($sale, $paymentBreakdown);

            return $sale->load('items');
        });
    }

    private function satisGeliriOlustur(Sale $sale, array $paymentBreakdown): void
    {
        $odemeSatirlari = [];

        foreach (['cash', 'card', 'transfer', 'credit'] as $tur) {
            $tutar = (float) ($paymentBreakdown[$tur . '_amount'] ?? 0);
            if ($tutar > 0) {
                $odemeSatirlari[] = ['payment_type' => $tur, 'amount' => $tutar];
            }
        }

        if (empty($odemeSatirlari)) {
            return;
        }

        $gelirTuru = $this->satisGelirTuru($sale->tenant_id);

        foreach ($odemeSatirlari as $satir) {
            Income::create([
                'tenant_id' => $sale->tenant_id,
                'branch_id' => $sale->branch_id,
                'external_id' => 'sale:' . $sale->id,
                'income_expense_type_id' => $gelirTuru->id,
                'type_name' => $gelirTuru->name,
                'note' => "Satış Geliri - {$sale->receipt_no}",
                'amount' => $satir['amount'],
                'payment_type' => $satir['payment_type'],
                'date' => $sale->sold_at?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
                'time' => $sale->sold_at?->format('H:i:s') ?? Carbon::now()->format('H:i:s'),
            ]);
        }
    }

    private function satisGeliriTersle(Sale $sale, string $neden): void
    {
        $odemeSatirlari = [];

        foreach (['cash', 'card', 'transfer', 'credit'] as $tur) {
            $tutar = (float) ($sale->{$tur . '_amount'} ?? 0);
            if ($tutar > 0) {
                $odemeSatirlari[] = ['payment_type' => $tur, 'amount' => $tutar];
            }
        }

        if (empty($odemeSatirlari)) {
            return;
        }

        $gelirTuru = $this->satisGelirTuru($sale->tenant_id);

        foreach ($odemeSatirlari as $satir) {
            Income::firstOrCreate(
                [
                    'tenant_id' => $sale->tenant_id,
                    'branch_id' => $sale->branch_id,
                    'external_id' => $neden . ':' . $sale->id . ':' . $satir['payment_type'],
                    'payment_type' => $satir['payment_type'],
                ],
                [
                    'income_expense_type_id' => $gelirTuru->id,
                    'type_name' => $gelirTuru->name,
                    'note' => "Satış {$neden} - {$sale->receipt_no}",
                    'amount' => -abs((float) $satir['amount']),
                    'date' => $sale->sold_at?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d'),
                    'time' => $sale->sold_at?->format('H:i:s') ?? Carbon::now()->format('H:i:s'),
                ]
            );
        }
    }

    private function satisGelirTuru(int $tenantId): IncomeExpenseType
    {
        return IncomeExpenseType::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => 'Satış Geliri',
                'direction' => 'income',
            ],
            [
                'is_active' => true,
            ]
        );
    }
    
    /**
     * Calculate totals from items array
     */
    public function calculateTotals(array $items, float $generalDiscount = 0): array
    {
        $subtotal = 0;
        $vatTotal = 0;
        $additionalTaxTotal = 0;
        $discountTotal = $generalDiscount;
        
        foreach ($items as &$item) {
            $qty = $item['quantity'] ?? 1;
            $unitPrice = $item['unit_price'] ?? 0;
            $itemDiscount = $item['discount'] ?? 0;
            $vatRate = $item['vat_rate'] ?? 20;
            
            $lineTotal = ($qty * $unitPrice) - $itemDiscount;
            $vatAmount = round($lineTotal * $vatRate / (100 + $vatRate), 2); // KDV dahil hesaplama
            $additionalTax = $item['additional_tax_amount'] ?? 0;
            
            $item['vat_amount'] = $vatAmount;
            $item['total'] = $lineTotal;
            
            $subtotal += ($lineTotal - $vatAmount);
            $vatTotal += $vatAmount;
            $additionalTaxTotal += $additionalTax;
            $discountTotal += $itemDiscount;
        }
        
        return [
            'subtotal' => round($subtotal, 2),
            'vat_total' => round($vatTotal, 2),
            'additional_tax_total' => round($additionalTaxTotal, 2),
            'discount_total' => round($discountTotal, 2),
            'grand_total' => round($subtotal + $vatTotal + $additionalTaxTotal - $generalDiscount, 2),
            'items' => $items,
        ];
    }
    
    /**
     * Generate receipt number: POS-YYYY-NNNNNN
     */
    public function generateReceiptNo(int $branchId): string
    {
        $year = Carbon::now()->format('Y');
        $lastSale = Sale::where('branch_id', $branchId)
            ->where('receipt_no', 'like', "POS-{$year}-%")
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();
        
        $nextNum = 1;
        if ($lastSale && $lastSale->receipt_no) {
            $parts = explode('-', $lastSale->receipt_no);
            $nextNum = (int)end($parts) + 1;
        }
        
        return "POS-{$year}-" . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Refund a sale
     */
    public function refundSale(int $saleId, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($saleId, $reason) {
            $sale = Sale::with('items')->where('id', $saleId)->lockForUpdate()->firstOrFail();
            
            if ($sale->status !== 'completed') {
                throw new \Exception('Sadece tamamlanmış satışlar iade edilebilir.');
            }
            
            $sale->update([
                'status' => 'refunded',
                'notes' => ($sale->notes ? $sale->notes . "\n" : '') . "İade: " . ($reason ?? 'Belirtilmedi'),
            ]);
            
            // Restore stock
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                    if ($product && !$product->is_service) {
                        $remainingStock = $product->adjustStockForBranch((int) $sale->branch_id, (float) $item->quantity);
                        
                        StockMovement::create([
                            'tenant_id' => $sale->tenant_id,
                            'branch_id' => $sale->branch_id,
                            'type' => 'return',
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'barcode' => $product->barcode,
                            'transaction_code' => $sale->receipt_no,
                            'note' => "İade: " . ($reason ?? ''),
                            'quantity' => $item->quantity,
                            'remaining' => $remainingStock,
                            'unit_price' => $item->unit_price,
                            'total' => $item->total,
                            'movement_date' => Carbon::now(),
                        ]);
                    }
                }
            }
            
            // Refund customer balance if credit sale
            $creditRefundAmount = 0;
            if ($sale->payment_method === 'credit' && $sale->customer_id) {
                $creditRefundAmount = $sale->grand_total;
            } elseif ($sale->payment_method === 'mixed' && $sale->credit_amount > 0 && $sale->customer_id) {
                $creditRefundAmount = $sale->credit_amount;
            }

            if ($creditRefundAmount > 0 && $sale->customer_id) {
                $customer = Customer::where('id', $sale->customer_id)->lockForUpdate()->first();
                if ($customer) {
                    $customer->increment('balance', $creditRefundAmount);
                    
                    AccountTransaction::create([
                        'tenant_id' => $sale->tenant_id,
                        'customer_id' => $customer->id,
                        'type' => 'refund',
                        'amount' => $creditRefundAmount,
                        'balance_after' => $customer->balance,
                        'description' => "İade: {$sale->receipt_no}",
                        'reference' => $sale->receipt_no,
                        'transaction_date' => Carbon::now(),
                    ]);
                }
            }

            $this->satisGeliriTersle($sale, 'iade');
            
            return $sale;
        });
    }
    
    /**
     * Cancel a sale (same as refund but status = cancelled)
     */
    public function cancelSale(int $saleId, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($saleId, $reason) {
            $sale = Sale::with('items')->where('id', $saleId)->lockForUpdate()->firstOrFail();

            if (in_array($sale->status, ['cancelled', 'refunded'])) {
                throw new \Exception('Bu satış zaten iptal edilmiş veya iade edilmiş.');
            }
            
            $sale->update([
                'status' => 'cancelled',
                'notes' => ($sale->notes ? $sale->notes . "\n" : '') . "İptal: " . ($reason ?? 'Belirtilmedi'),
            ]);
            
            // Stock restoration + movement kaydı
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                    if ($product && !$product->is_service) {
                        $remainingStock = $product->adjustStockForBranch((int) $sale->branch_id, (float) $item->quantity);
                        
                        StockMovement::create([
                            'tenant_id' => $sale->tenant_id,
                            'branch_id' => $sale->branch_id,
                            'type' => 'return',
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'barcode' => $product->barcode,
                            'transaction_code' => $sale->receipt_no,
                            'note' => "İptal: " . ($reason ?? ''),
                            'quantity' => $item->quantity,
                            'remaining' => $remainingStock,
                            'unit_price' => $item->unit_price,
                            'total' => $item->total,
                            'movement_date' => Carbon::now(),
                        ]);
                    }
                }
            }
            
            // Veresiye bakiye geri yükle (credit veya mixed'deki credit kısmı)
            $creditCancelAmount = 0;
            if ($sale->payment_method === 'credit' && $sale->customer_id) {
                $creditCancelAmount = $sale->grand_total;
            } elseif ($sale->payment_method === 'mixed' && $sale->credit_amount > 0 && $sale->customer_id) {
                $creditCancelAmount = $sale->credit_amount;
            }

            if ($creditCancelAmount > 0 && $sale->customer_id) {
                $customer = Customer::where('id', $sale->customer_id)->lockForUpdate()->first();
                if ($customer) {
                    $customer->increment('balance', $creditCancelAmount);
                    AccountTransaction::create([
                        'tenant_id' => $sale->tenant_id,
                        'customer_id' => $customer->id,
                        'type' => 'refund',
                        'amount' => $creditCancelAmount,
                        'balance_after' => $customer->balance,
                        'description' => "İptal: {$sale->receipt_no}",
                        'reference' => $sale->receipt_no,
                        'transaction_date' => Carbon::now(),
                    ]);
                }
            }

            $this->satisGeliriTersle($sale, 'iptal');
            
            return $sale;
        });
    }
}
