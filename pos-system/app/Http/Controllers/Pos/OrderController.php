<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');
        $query = Order::with(['user', 'customer', 'tableSession.table', 'items.product'])
            ->where('branch_id', $branchId)
            ->orderBy('ordered_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        if ($request->filled('date')) {
            $query->whereDate('ordered_at', $request->date);
        } else {
            $query->whereDate('ordered_at', Carbon::today());
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$s}%"));
            });
        }

        $orders = $query->paginate(50);

        $statsBase = Order::where('branch_id', $branchId)->whereDate('ordered_at', Carbon::today());
        $stats = [
            'total_today' => (clone $statsBase)->count(),
            'pending' => (clone $statsBase)->where('status', 'pending')->count(),
            'preparing' => (clone $statsBase)->where('status', 'preparing')->count(),
            'completed' => (clone $statsBase)->where('status', 'completed')->count(),
            'cancelled' => (clone $statsBase)->where('status', 'cancelled')->count(),
            'total_revenue' => (clone $statsBase)->whereNotIn('status', ['cancelled'])->sum('grand_total'),
        ];

        return view('pos.orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'customer', 'tableSession.table', 'items.product']);
        return response()->json(['order' => $order]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate(['status' => 'required|in:pending,preparing,ready,served,completed,cancelled']);
        $order->update(['status' => $request->status]);
        return response()->json(['success' => true, 'order' => $order->fresh()]);
    }
}
