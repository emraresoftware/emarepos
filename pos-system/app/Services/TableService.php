<?php
namespace App\Services;

use App\Models\RestaurantTable;
use App\Models\TableSession;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TableService
{
    public function openTable(int $tableId, int $userId, ?int $customerId = null, int $customerCount = 1): TableSession
    {
        $table = RestaurantTable::findOrFail($tableId);
        
        if ($table->status !== 'empty') {
            // Aktif session yoksa statüsü stale — otomatik sıfırla
            $activeSession = TableSession::where('restaurant_table_id', $tableId)
                ->where('status', 'open')
                ->first();
            if (!$activeSession) {
                $table->update(['status' => 'empty']);
            } else {
                throw new \Exception('Bu masa şu an müsait değil.');
            }
        }
        
        return DB::transaction(function () use ($table, $userId, $customerId, $customerCount) {
            $session = TableSession::create([
                'tenant_id' => session('tenant_id'),
                'restaurant_table_id' => $table->id,
                'opened_by' => $userId,
                'customer_id' => $customerId,
                'customer_count' => $customerCount,
                'status' => 'open',
                'opened_at' => Carbon::now(),
            ]);
            
            $table->update(['status' => 'occupied']);
            
            return $session;
        });
    }
    
    public function closeTable(int $sessionId, int $userId): TableSession
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            $session = TableSession::with('table')->findOrFail($sessionId);
            
            $session->update([
                'closed_by' => $userId,
                'status' => 'closed',
                'closed_at' => Carbon::now(),
            ]);
            
            $session->table->update(['status' => 'empty']);
            
            return $session;
        });
    }
    
    public function addOrder(int $sessionId, array $data): Order
    {
        return DB::transaction(function () use ($sessionId, $data) {
            $session = TableSession::findOrFail($sessionId);
            
            $orderNumber = 'ORD-' . Carbon::now()->format('Ymd') . '-' . str_pad(
                Order::whereDate('created_at', Carbon::today())->count() + 1,
                4, '0', STR_PAD_LEFT
            );
            
            $items = $data['items'] ?? [];
            $subtotal = 0;
            $vatTotal = 0;
            $discountTotal = 0;
            
            foreach ($items as &$item) {
                $qty = $item['quantity'] ?? 1;
                $unitPrice = $item['unit_price'] ?? 0;
                $discount = $item['discount'] ?? 0;
                $vatRate = $item['vat_rate'] ?? 20;
                
                $lineTotal = ($qty * $unitPrice) - $discount;
                $vatAmount = round($lineTotal * $vatRate / (100 + $vatRate), 2);
                
                $item['vat_amount'] = $vatAmount;
                $item['total'] = $lineTotal;
                
                $subtotal += ($lineTotal - $vatAmount);
                $vatTotal += $vatAmount;
                $discountTotal += $discount;
            }
            
            $order = Order::create([
                'tenant_id' => session('tenant_id'),
                'branch_id' => $data['branch_id'] ?? session('branch_id'),
                'table_session_id' => $sessionId,
                'order_number' => $orderNumber,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'customer_id' => $session->customer_id,
                'status' => 'pending',
                'order_type' => 'dine_in',
                'total_items' => count($items),
                'subtotal' => round($subtotal, 2),
                'vat_total' => round($vatTotal, 2),
                'discount_total' => round($discountTotal, 2),
                'grand_total' => round($subtotal + $vatTotal, 2),
                'notes' => $data['notes'] ?? null,
                'kitchen_notes' => $data['kitchen_notes'] ?? null,
                'ordered_at' => Carbon::now(),
            ]);
            
            foreach ($items as $item) {
                $product = \App\Models\Product::find($item['product_id'] ?? null);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'] ?? $product?->name ?? 'Bilinmeyen',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'vat_rate' => $item['vat_rate'] ?? 20,
                    'vat_amount' => $item['vat_amount'] ?? 0,
                    'total' => $item['total'] ?? 0,
                    'status' => 'pending',
                    'notes' => $item['notes'] ?? null,
                ]);
            }
            
            return $order->load('items');
        });
    }
    
    public function getTableSummary(int $sessionId): array
    {
        $session = TableSession::with(['orders.items', 'table', 'customer'])->findOrFail($sessionId);
        
        $totalAmount = $session->orders->where('status', '!=', 'cancelled')->sum('grand_total');
        $totalItems = $session->orders->where('status', '!=', 'cancelled')->sum('total_items');
        
        return [
            'session' => $session,
            'total_amount' => $totalAmount,
            'total_items' => $totalItems,
            'orders' => $session->orders,
        ];
    }
}
