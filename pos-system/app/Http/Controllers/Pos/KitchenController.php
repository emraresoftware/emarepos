<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id');
        
        $orders = Order::where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->with(['items', 'tableSession.table', 'user'])
            ->orderBy('ordered_at', 'asc')
            ->get();

        return view('pos.kitchen.index', compact('orders'));
    }

    public function getOrders()
    {
        $branchId = session('branch_id');
        
        $orders = Order::where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->with(['items', 'tableSession.table', 'user'])
            ->orderBy('ordered_at', 'asc')
            ->get();

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate(['status' => 'required|in:preparing,ready,served,completed,cancelled']);
        
        $order->update(['status' => $request->status]);
        
        // If order is ready or served, update all pending items too
        // order_items enum: pending,preparing,ready,served,cancelled (no 'completed')
        if (in_array($request->status, ['ready', 'served'])) {
            $order->items()->where('status', '!=', 'cancelled')->update(['status' => $request->status]);
        } elseif ($request->status === 'completed') {
            $order->items()->where('status', '!=', 'cancelled')->update(['status' => 'served']);
        }
        
        return response()->json(['success' => true, 'order' => $order->fresh(['items'])]);
    }

    public function updateItemStatus(Request $request, OrderItem $item)
    {
        $request->validate(['status' => 'required|in:preparing,ready,served,cancelled']);
        
        $item->update(['status' => $request->status]);
        
        // Check if all items of order are ready
        $order = $item->order;
        $allReady = $order->items()->where('status', '!=', 'cancelled')->where('status', '!=', 'ready')->doesntExist();
        if ($allReady) {
            $order->update(['status' => 'ready']);
        }
        
        return response()->json(['success' => true, 'item' => $item]);
    }
}
