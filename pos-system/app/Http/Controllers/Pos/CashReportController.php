<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\PosTerminal;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CashReportController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');
        $terminals = PosTerminal::where('tenant_id', session('tenant_id'))
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $selectedTerminalId = null;
        if ($request->filled('terminal_id')) {
            $selectedTerminalId = $terminals
                ->where('id', (int) $request->input('terminal_id'))
                ->value('id');
        }

        if ($selectedTerminalId && ! $terminals->contains('id', $selectedTerminalId)) {
            $selectedTerminalId = null;
        }

        $query = CashRegister::with('user')
            ->where('branch_id', $branchId)
            ->when($selectedTerminalId, fn ($q) => $q->where('terminal_id', $selectedTerminalId))
            ->orderBy('opened_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('opened_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('opened_at', '<=', $request->end_date);
        }

        $registers = $query->paginate(30)->withQueryString();

        $registerSalesTotals = [];
        if ($registers->count() > 0) {
            $registerIds = $registers->pluck('id')->toArray();
            $placeholders = implode(',', array_fill(0, count($registerIds), '?'));
            $rows = \DB::select("
                SELECT r.id,
                       COALESCE(SUM(s.grand_total), 0) as total_sales,
                       COALESCE(SUM(s.cash_amount), 0) as total_cash,
                       COALESCE(SUM(s.card_amount), 0) as total_card
                FROM cash_registers r
                LEFT JOIN sales s ON s.branch_id = r.branch_id
                    AND ((r.terminal_id IS NULL AND s.terminal_id IS NULL) OR s.terminal_id = r.terminal_id)
                    AND s.status = 'completed'
                    AND s.sold_at >= r.opened_at
                    AND (r.closed_at IS NULL OR s.sold_at <= r.closed_at)
                WHERE r.id IN ({$placeholders})
                GROUP BY r.id
            ", $registerIds);

            foreach ($rows as $row) {
                $registerSalesTotals[$row->id] = [
                    'total_sales' => (float) $row->total_sales,
                    'total_cash' => (float) $row->total_cash,
                    'total_card' => (float) $row->total_card,
                ];
            }
        }

        $salesStatsQuery = Sale::where('branch_id', $branchId)
            ->when($selectedTerminalId, fn ($q) => $q->where('terminal_id', $selectedTerminalId))
            ->where('status', 'completed')
            ->when($request->filled('start_date'), fn($q) => $q->whereDate('sold_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->whereDate('sold_at', '<=', $request->end_date));

        $stats = [
            'total_registers' => CashRegister::where('branch_id', $branchId)->when($selectedTerminalId, fn ($q) => $q->where('terminal_id', $selectedTerminalId))->count(),
            'total_sales_all' => (clone $salesStatsQuery)->sum('grand_total'),
            'total_cash_all'  => (clone $salesStatsQuery)->sum('cash_amount'),
            'total_card_all'  => (clone $salesStatsQuery)->sum('card_amount'),
            'avg_difference'  => CashRegister::where('branch_id', $branchId)->when($selectedTerminalId, fn ($q) => $q->where('terminal_id', $selectedTerminalId))->where('status', 'closed')->avg('difference') ?? 0,
            // Veresiye: Sales tablosundan hesapla (cash_register'da sütun yok)
            'total_credit_all' => (clone $salesStatsQuery)->where('credit_amount', '>', 0)->sum('credit_amount'),
        ];

        // Her kayıt için veresiye toplamını tek JOIN sorgusu ile hesapla (N+1 yok)
        $registerIds = $registers->pluck('id')->toArray();
        $creditByRegister = [];
        if (!empty($registerIds)) {
            $placeholders = implode(',', array_fill(0, count($registerIds), '?'));
            $rows = \DB::select("
                SELECT r.id, COALESCE(SUM(s.credit_amount), 0) as credit_total
                FROM cash_registers r
                LEFT JOIN sales s ON s.branch_id = r.branch_id
                    AND ((r.terminal_id IS NULL AND s.terminal_id IS NULL) OR s.terminal_id = r.terminal_id)
                    AND s.status = 'completed'
                    AND s.credit_amount > 0
                    AND s.sold_at >= r.opened_at
                    AND (r.closed_at IS NULL OR s.sold_at <= r.closed_at)
                WHERE r.id IN ({$placeholders})
                GROUP BY r.id
            ", $registerIds);
            foreach ($rows as $row) {
                $creditByRegister[$row->id] = (float) $row->credit_total;
            }
        }

        return view('pos.cash-report.index', compact('registers', 'stats', 'creditByRegister', 'registerSalesTotals', 'terminals', 'selectedTerminalId'));
    }

    public function show(CashRegister $register)
    {
        if ($register->branch_id !== (int) session('branch_id')) {
            return response()->json(['error' => 'Yetkiniz yok.'], 403);
        }
        $register->load('user');

        // Bu kasanın satışları
        $sales = Sale::where('branch_id', $register->branch_id)
            ->when($register->terminal_id !== null, fn ($q) => $q->where('terminal_id', $register->terminal_id))
            ->whereBetween('sold_at', [$register->opened_at, $register->closed_at ?? now()])
            ->orderBy('sold_at', 'desc')
            ->get();

        return response()->json([
            'register' => $register,
            'sales' => $sales,
        ]);
    }
}
