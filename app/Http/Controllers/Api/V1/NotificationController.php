<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Liste des notifications de l'utilisateur connecté
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');

        // Filtres optionnels
        if ($request->has('category')) {
            $query->ofCategory($request->category);
        }

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        // Pagination
        $perPage = $request->integer('per_page', 20);
        $notifications = $query->paginate($perPage);

        return $this->paginated(
            $notifications->through(fn ($n) => $n->toApiArray()),
            'Notifications récupérées'
        );
    }

    /**
     * Récupérer une notification spécifique
     */
    public function show(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success($notification->toApiArray());
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $notification->markAsRead();

        return $this->success($notification->toApiArray(), 'Notification marquée comme lue');
    }

    /**
     * Marquer plusieurs notifications comme lues
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);

        $count = $this->notificationService->markAsRead(
            auth()->user(),
            $request->notification_ids
        );

        return $this->success([
            'marked_count' => $count,
        ], "{$count} notification(s) marquée(s) comme lue(s)");
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(auth()->user());

        return $this->success([
            'marked_count' => $count,
        ], 'Toutes les notifications ont été marquées comme lues');
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $notification->delete();

        return $this->noContent();
    }

    /**
     * Supprimer plusieurs notifications
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);

        $count = Notification::where('user_id', auth()->id())
            ->whereIn('id', $request->notification_ids)
            ->delete();

        return $this->success([
            'deleted_count' => $count,
        ], "{$count} notification(s) supprimée(s)");
    }

    /**
     * Récupérer le compteur de notifications non lues
     */
    public function unreadCount(): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount(auth()->user());

        return $this->success([
            'unread_count' => $count,
        ]);
    }

    /**
     * Récupérer les notifications par catégorie avec compteurs
     */
    public function summary(): JsonResponse
    {
        $user = auth()->user();

        $categories = [
            Notification::CATEGORY_DOCUMENT,
            Notification::CATEGORY_KYC,
            Notification::CATEGORY_ENGAGEMENT,
            Notification::CATEGORY_PAYMENT,
            Notification::CATEGORY_SYSTEM,
        ];

        $summary = [];
        foreach ($categories as $category) {
            $summary[$category] = [
                'total' => Notification::where('user_id', $user->id)
                    ->ofCategory($category)
                    ->count(),
                'unread' => Notification::where('user_id', $user->id)
                    ->ofCategory($category)
                    ->unread()
                    ->count(),
            ];
        }

        $summary['all'] = [
            'total' => Notification::where('user_id', $user->id)->count(),
            'unread' => Notification::where('user_id', $user->id)->unread()->count(),
        ];

        return $this->success($summary);
    }

    /**
     * Récupérer les notifications récentes (dernières 24h)
     */
    public function recent(): JsonResponse
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($n) => $n->toApiArray());

        return $this->success([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount(auth()->user()),
        ]);
    }
}
