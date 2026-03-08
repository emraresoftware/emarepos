<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Income;
use App\Models\Expense;
use App\Models\IncomeExpenseType;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IncomeExpenseController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = session('tenant_id');
        $branchId = session('branch_id');

        // --- Filtreler ---
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $typeFilter   = $request->input('type_id');
        $searchFilter = $request->input('search');
        $tab          = $request->input('tab', 'income'); // income | expense | types

        // --- İstatistikler ---
        $totalIncome  = Income::where('branch_id', $branchId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');
        $totalExpense = Expense::where('branch_id', $branchId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');
        $netBalance   = $totalIncome - $totalExpense;
        $thisMonthIncome  = Income::where('branch_id', $branchId)
            ->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->toDateString()])->sum('amount');
        $thisMonthExpense = Expense::where('branch_id', $branchId)
            ->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->toDateString()])->sum('amount');

        // --- Gelirler ---
        $incomeQuery = Income::where('branch_id', $branchId)->with('type')->orderBy('date', 'desc')->orderBy('id', 'desc');
        $incomeQuery->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
        if ($typeFilter) $incomeQuery->where('income_expense_type_id', $typeFilter);
        if ($searchFilter) $incomeQuery->where('note', 'like', "%{$searchFilter}%");
        $incomes = $incomeQuery->paginate(20, ['*'], 'income_page')->withQueryString();

        // --- Giderler ---
        $expenseQuery = Expense::where('branch_id', $branchId)->with('type')->orderBy('date', 'desc')->orderBy('id', 'desc');
        $expenseQuery->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
        if ($typeFilter) $expenseQuery->where('income_expense_type_id', $typeFilter);
        if ($searchFilter) $expenseQuery->where('note', 'like', "%{$searchFilter}%");
        $expenses = $expenseQuery->paginate(20, ['*'], 'expense_page')->withQueryString();

        // --- Türler ---
        $incomeTypes  = IncomeExpenseType::where('direction', 'income')->orderBy('name')->get();
        $expenseTypes = IncomeExpenseType::where('direction', 'expense')->orderBy('name')->get();
        $allTypes     = IncomeExpenseType::orderBy('direction')->orderBy('name')->get();

        return view('pos.income-expense.index', compact(
            'incomes', 'expenses', 'incomeTypes', 'expenseTypes', 'allTypes',
            'totalIncome', 'totalExpense', 'netBalance',
            'thisMonthIncome', 'thisMonthExpense',
            'startDate', 'endDate', 'tab'
        ));
    }

    // ─── Gelir CRUD ─────────────────────────────────────────────────────

    public function storeIncome(Request $request)
    {
        $data = $request->validate([
            'income_expense_type_id' => 'required|exists:income_expense_types,id',
            'amount'       => 'required|numeric|min:0.01',
            'note'         => 'nullable|string|max:500',
            'payment_type' => 'nullable|string',
            'date'         => 'required|date',
        ]);
        $data['tenant_id']  = session('tenant_id');
        $data['branch_id']  = session('branch_id');
        $data['type_name']  = IncomeExpenseType::find($data['income_expense_type_id'])?->name;
        $data['payment_type'] = $data['payment_type'] ?? 'cash';

        $income = Income::create($data);
        $income->load('type');
        return response()->json(['success' => true, 'income' => $income]);
    }

    public function destroyIncome(Income $income)
    {
        $income->delete();
        return response()->json(['success' => true]);
    }

    public function updateIncome(Request $request, Income $income)
    {
        $data = $request->validate([
            'income_expense_type_id' => 'required|exists:income_expense_types,id',
            'amount'       => 'required|numeric|min:0.01',
            'note'         => 'nullable|string|max:500',
            'payment_type' => 'nullable|string',
            'date'         => 'required|date',
        ]);
        $data['type_name'] = IncomeExpenseType::find($data['income_expense_type_id'])?->name;
        $income->update($data);
        $income->load('type');
        return response()->json(['success' => true, 'income' => $income]);
    }

    // ─── Gider CRUD ─────────────────────────────────────────────────────

    public function storeExpense(Request $request)
    {
        $data = $request->validate([
            'income_expense_type_id' => 'required|exists:income_expense_types,id',
            'amount'       => 'required|numeric|min:0.01',
            'note'         => 'nullable|string|max:500',
            'payment_type' => 'nullable|string',
            'date'         => 'required|date',
        ]);
        $data['tenant_id']  = session('tenant_id');
        $data['branch_id']  = session('branch_id');
        $data['type_name']  = IncomeExpenseType::find($data['income_expense_type_id'])?->name;
        $data['payment_type'] = $data['payment_type'] ?? 'cash';

        $expense = Expense::create($data);
        $expense->load('type');
        return response()->json(['success' => true, 'expense' => $expense]);
    }

    public function destroyExpense(Expense $expense)
    {
        $expense->delete();
        return response()->json(['success' => true]);
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'income_expense_type_id' => 'required|exists:income_expense_types,id',
            'amount'       => 'required|numeric|min:0.01',
            'note'         => 'nullable|string|max:500',
            'payment_type' => 'nullable|string',
            'date'         => 'required|date',
        ]);
        $data['type_name'] = IncomeExpenseType::find($data['income_expense_type_id'])?->name;
        $expense->update($data);
        $expense->load('type');
        return response()->json(['success' => true, 'expense' => $expense]);
    }

    // ─── Tür CRUD ───────────────────────────────────────────────────────

    public function storeType(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'direction' => 'required|in:income,expense',
        ]);
        $data['tenant_id'] = session('tenant_id');
        $data['is_active'] = true;

        $type = IncomeExpenseType::create($data);
        return response()->json(['success' => true, 'type' => $type]);
    }

    public function destroyType(IncomeExpenseType $type)
    {
        // Bağlı kayıt varsa sil
        $type->delete();
        return response()->json(['success' => true]);
    }
}
