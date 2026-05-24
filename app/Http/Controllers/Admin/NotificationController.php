<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Afficher la liste des notifications envoyées
     */
    public function index(Request $request): Response
    {
        $query = Notification::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('category')) {
            $query->ofCategory($request->category);
        }

        $notifications = $query->paginate(20)->withQueryString();

        return Inertia::render('admin/notifications/index', [
            'notifications' => $notifications,
            'filters' => $request->only(['type', 'category']),
            'types' => [
                Notification::TYPE_ENGAGEMENT,
                Notification::TYPE_SYSTEM,
                Notification::TYPE_DOCUMENT_EXPIRING,
                Notification::TYPE_DOCUMENT_EXPIRED,
                Notification::TYPE_KYC_STATUS,
                Notification::TYPE_PAYMENT,
            ],
            'categories' => [
                Notification::CATEGORY_ENGAGEMENT,
                Notification::CATEGORY_SYSTEM,
                Notification::CATEGORY_DOCUMENT,
                Notification::CATEGORY_KYC,
                Notification::CATEGORY_PAYMENT,
            ],
        ]);
    }

    /**
     * Formulaire pour créer une notification d'engagement
     */
    public function create(): Response
    {
        $users = User::select('id', 'first_name', 'last_name', 'phone', 'email')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('admin/notifications/create', [
            'users' => $users,
            'priorities' => [
                Notification::PRIORITY_LOW,
                Notification::PRIORITY_NORMAL,
                Notification::PRIORITY_HIGH,
                Notification::PRIORITY_URGENT,
            ],
        ]);
    }

    /**
     * Envoyer une notification d'engagement à un ou plusieurs utilisateurs
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'broadcast' => 'boolean',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'action_url' => 'nullable|string|max:500',
            'action_type' => 'nullable|string|max:50',
            'send_sms' => 'boolean',
        ]);

        $options = [
            'priority' => $validated['priority'] ?? Notification::PRIORITY_NORMAL,
            'action_url' => $validated['action_url'] ?? null,
            'action_type' => $validated['action_type'] ?? null,
            'send_sms' => $validated['send_sms'] ?? false,
        ];

        // Broadcast à tous les utilisateurs
        if ($request->boolean('broadcast')) {
            $count = $this->notificationService->broadcastEngagementMessage(
                $validated['title'],
                $validated['body'],
                null,
                $options
            );

            return response()->json([
                'success' => true,
                'message' => "Notification envoyée à {$count} utilisateurs",
                'recipients_count' => $count,
            ]);
        }

        // Envoyer à des utilisateurs spécifiques
        if (empty($validated['user_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner au moins un utilisateur ou activer le broadcast',
            ], 422);
        }

        $users = User::whereIn('id', $validated['user_ids'])->get();
        $count = 0;

        foreach ($users as $user) {
            $this->notificationService->sendEngagementMessage(
                $user,
                $validated['title'],
                $validated['body'],
                $options
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Notification envoyée à {$count} utilisateur(s)",
            'recipients_count' => $count,
        ]);
    }

    /**
     * Statistiques des notifications
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Notification::count(),
            'unread' => Notification::unread()->count(),
            'read' => Notification::read()->count(),
            'by_category' => [],
            'by_type' => [],
            'last_7_days' => Notification::where('created_at', '>=', now()->subDays(7))->count(),
            'last_30_days' => Notification::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        // Par catégorie
        $categories = [
            Notification::CATEGORY_DOCUMENT,
            Notification::CATEGORY_KYC,
            Notification::CATEGORY_ENGAGEMENT,
            Notification::CATEGORY_PAYMENT,
            Notification::CATEGORY_SYSTEM,
        ];

        foreach ($categories as $category) {
            $stats['by_category'][$category] = Notification::ofCategory($category)->count();
        }

        // Par type
        $types = [
            Notification::TYPE_DOCUMENT_EXPIRING,
            Notification::TYPE_DOCUMENT_EXPIRED,
            Notification::TYPE_KYC_STATUS,
            Notification::TYPE_ENGAGEMENT,
            Notification::TYPE_PAYMENT,
            Notification::TYPE_SYSTEM,
        ];

        foreach ($types as $type) {
            $stats['by_type'][$type] = Notification::ofType($type)->count();
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
