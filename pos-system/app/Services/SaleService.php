<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
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
                'cash_amount' => $data['cash_amount'] ?? 0,
                'card_amount' => $data['card_amount'] ?? 0,
                'credit_amount' => $data['credit_amount'] ?? 0,
                'transfer_amount' => $data['transfer_amount'] ?? 0,
                'status' => 'completed',
                'staff_name' => $data['staff_name'] ?? auth()->user()?->name,
                'application' => $data['application'] ?? 'pos',
                'notes' => $data['notes'] ?? null,
                'sold_at' => $data['sold_at'] ?? Carbon::now(),
            ]);
            
            // Create sale items and update stock (use calculated items for correct vat/totals)
            $calculatedItems = $calculated['items'];
            foreach ($calculatedItems as $item) {
                $product = Product::find($item['product_id']);
                
                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'] ?? $product?->name ?? 'Bilinmeyen Ürün',
                    'barcode' => $item['barcode'] ?? $product?->barcode,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? $product?->sale_price ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'vat_rate' => $item['vat_rate'] ?? $product?->vat_rate ?? 20,
                    'vat_amount' => $item['vat_amount'] ?? 0,
                    'additional_taxes' => $item['additional_taxes'] ?? null,
                    'additional_tax_amount' => $item['additional_tax_amount'] ?? 0,
                    'total' => $item['total'] ?? 0,
                ]);
                
                // Update stock
                if ($product && !$product->is_service) {
                    $product->decrement('stock_quantity', $item['quantity'] ?? 1);
                    
                    // Create stock movement
                    StockMovement::create([
                        'tenant_id' => $tenantId,
                        'type' => 'sale',
                        'barcode' => $product->barcode,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'transaction_code' => $receiptNo,
                        'quantity' => -($item['quantity'] ?? 1),
                        'remaining' => $product->stock_quantity,
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
            } elseif ($data['payment_method'] === 'mixed' && !empty($data['credit_amount'])) {
                $creditAmount = (float)$data['credit_amount'];
            }

            if ($creditAmount > 0 && !empty($data['customer_id'])) {
                $customer = Customer::find($data['customer_id']);
                if ($customer) {
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
                    $kitchenItems = array_filter($calculated['items'], function ($item) {
                        $p = Product::find($item['product_id']);
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
                            $kProduct = Product::find($item['product_id']);
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
                        'items' => array_map(fn($item) => [
                            'product_name' => $item['product_name'] ?? Product::find($item['product_id'])?->name ?? '',
                            'quantity' => $item['quantity'] ?? 1,
                            'note' => $item['note'] ?? null,
                        ], $items),
                    ];
                    PrinterService::printKitchenTicket($kitchenData);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Mutfak fişi yazdırılamadı: ' . $e->getMessage(), ['sale_id' => $sale->id]);
            }

            return $sale->load('items');
        });
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
            $sale = Sale::with('items')->findOrFail($saleId);
            
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
                    $product = Product::find($item->product_id);
                    if ($product && !$product->is_service) {
                        $product->increment('stock_quantity', $item->quantity);
                        
                        StockMovement::create([
                            'tenant_id' => $sale->tenant_id,
                            'type' => 'return',
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'barcode' => $product->barcode,
                            'transaction_code' => $sale->receipt_no,
                            'note' => "İade: " . ($reason ?? ''),
                            'quantity' => $item->quantity,
                            'remaining' => $product->stock_quantity,
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
                $customer = Customer::find($sale->customer_id);
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
            
            return $sale;
        });
    }
    
    /**
     * Cancel a sale (same as refund but status = cancelled)
     */
    public function cancelSale(int $saleId, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($saleId, $reason) {
            $sale = Sale::with('items')->findOrFail($saleId);
            
            $sale->update([
                'status' => 'cancelled',
                'notes' => ($sale->notes ? $sale->notes . "\n" : '') . "İptal: " . ($reason ?? 'Belirtilmedi'),
            ]);
            
            // Stock restoration + movement kaydı
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product && !$product->is_service) {
                        $product->increment('stock_quantity', $item->quantity);
                        
                        StockMovement::create([
                            'tenant_id' => $sale->tenant_id,
                            'type' => 'return',
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'barcode' => $product->barcode,
                            'transaction_code' => $sale->receipt_no,
                            'note' => "İptal: " . ($reason ?? ''),
                            'quantity' => $item->quantity,
                            'remaining' => $product->stock_quantity,
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
                $customer = Customer::find($sale->customer_id);
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
            
            return $sale;
        });
    }
}
