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
        $query = Order::with(['user', 'customer', 'tableSession.table', 'items.product'])
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

        $stats = [
            'total_today' => Order::whereDate('ordered_at', Carbon::today())->count(),
            'pending' => Order::whereDate('ordered_at', Carbon::today())->where('status', 'pending')->count(),
            'preparing' => Order::whereDate('ordered_at', Carbon::today())->where('status', 'preparing')->count(),
            'completed' => Order::whereDate('ordered_at', Carbon::today())->where('status', 'completed')->count(),
            'cancelled' => Order::whereDate('ordered_at', Carbon::today())->where('status', 'cancelled')->count(),
            'total_revenue' => Order::whereDate('ordered_at', Carbon::today())->whereNotIn('status', ['cancelled'])->sum('grand_total'),
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
