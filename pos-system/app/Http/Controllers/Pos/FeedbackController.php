<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    /**
     * Widget'tan gelen yeni geri bildirim kaydı
     * POST /api/feedback
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'  => 'required|string|min:3|max:2000',
            'category' => 'nullable|in:bug,suggestion,question,other',
            'priority' => 'nullable|in:low,normal,high,critical',
            'page_url' => 'nullable|string|max:500',
        ]);

        $feedback = Feedback::create([
            'tenant_id'   => session('tenant_id'),
            'session_key' => session()->getId(),
            'user_name'   => session('user_name') ?? session('user_email'),
            'category'    => $validated['category'] ?? 'other',
            'priority'    => $validated['priority'] ?? 'normal',
            'message'     => $validated['message'],
            'page_url'    => $validated['page_url'] ?? $request->header('Referer'),
            'status'      => 'open',
        ]);

        return response()->json([
            'success' => true,
            'id'      => $feedback->id,
            'message' => 'Geri bildiriminiz alındı. Teşekkürler!',
        ]);
    }

    /**
     * Kullanıcının kendi geri bildirimleri
     * GET /api/feedback/my
     */
    public function my(): JsonResponse
    {
        $sessionKey = session()->getId();

        $feedbacks = Feedback::where('session_key', $sessionKey)
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->map(fn($f) => [
                'id'             => $f->id,
                'message'        => $f->message,
                'category'       => $f->category,
                'category_label' => $f->category_label,
                'priority'       => $f->priority,
                'status'         => $f->status,
                'status_label'   => $f->status_label,
                'admin_reply'    => $f->admin_reply,
                'replied_at'     => $f->replied_at?->format('d.m.Y H:i'),
                'created_at'     => $f->created_at->format('d.m.Y H:i'),
            ]);

        return response()->json(['messages' => $feedbacks]);
    }

    /**
     * Admin: Geri bildirim listesi sayfası
     * GET /feedback
     */
    public function index(Request $request)
    {
        $query = Feedback::query();

        if ($tenantId = session('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        $feedbacks  = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $totalCount = Feedback::when(session('tenant_id'), fn($q) => $q->where('tenant_id', session('tenant_id')))->count();
        $openCount  = Feedback::when(session('tenant_id'), fn($q) => $q->where('tenant_id', session('tenant_id')))->where('status', 'open')->count();
        $stats = [
            'total'       => $totalCount,
            'open'        => $openCount,
            'in_progress' => Feedback::when(session('tenant_id'), fn($q) => $q->where('tenant_id', session('tenant_id')))->where('status', 'in_progress')->count(),
            'resolved'    => Feedback::when(session('tenant_id'), fn($q) => $q->where('tenant_id', session('tenant_id')))->where('status', 'resolved')->count(),
        ];

        return view('pos.feedback.index', compact('feedbacks', 'stats'));
    }

    /**
     * Admin: Durumu güncelle
     * PATCH /feedback/{feedback}/status
     */
    public function updateStatus(Request $request, Feedback $feedback): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $feedback->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'status'  => $feedback->status,
            'label'   => Feedback::STATUS_LABELS[$feedback->status],
        ]);
    }

    /**
     * Admin: Yanıt gönder
     * POST /feedback/{feedback}/reply
     */
    public function reply(Request $request, Feedback $feedback): JsonResponse
    {
        $request->validate([
            'admin_reply' => 'required|string|min:3|max:3000',
        ]);

        $feedback->update([
            'admin_reply' => $request->admin_reply,
            'replied_at'  => now(),
            'status'      => 'resolved',
        ]);

        return response()->json([
            'success'   => true,
            'replied_at' => $feedback->replied_at->format('d.m.Y H:i'),
        ]);
    }

    /**
     * Admin: Sil
     * DELETE /feedback/{feedback}
     */
    public function destroy(Feedback $feedback): JsonResponse
    {
        $feedback->delete();

        return response()->json(['success' => true]);
    }
}
