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

        $orders = $query->paginate(50)->withQueryString();

        // 6 ayrı sorgu yerine tek aggregate sorgu
        $agg = Order::where('branch_id', $branchId)
            ->whereDate('ordered_at', Carbon::today())
            ->selectRaw("
                COUNT(*) as total_today,
                SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                COALESCE(SUM(CASE WHEN status != 'cancelled' THEN grand_total ELSE 0 END), 0) as total_revenue
            ")
            ->first();
        $stats = [
            'total_today'   => (int) ($agg->total_today ?? 0),
            'pending'       => (int) ($agg->pending ?? 0),
            'preparing'     => (int) ($agg->preparing ?? 0),
            'completed'     => (int) ($agg->completed ?? 0),
            'cancelled'     => (int) ($agg->cancelled ?? 0),
            'total_revenue' => (float) ($agg->total_revenue ?? 0),
        ];

        return view('pos.orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order)
    {
        if ($order->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
        $order->load(['user', 'customer', 'tableSession.table', 'items.product']);
        return response()->json(['order' => $order]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($order->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
        $request->validate(['status' => 'required|in:pending,preparing,ready,served,completed,cancelled']);
        $order->update(['status' => $request->status]);
        return response()->json(['success' => true, 'order' => $order->fresh()]);
    }
}
