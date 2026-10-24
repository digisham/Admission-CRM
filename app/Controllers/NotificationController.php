<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\View;
use App\Models\Notification;

class NotificationController
{
    public function unread(): void
    {
        header('Content-Type: application/json');

        if (!Auth::check()) {
            echo json_encode([
                'status' => 'unauthenticated',
                'unread_count' => 0,
                'notifications' => [],
                'latest_id' => 0
            ]);
            exit;
        }

        $userId = Auth::id();
        $unreadCount = Notification::getUnreadCount($userId);
        $notifications = Notification::getUnreadForUser($userId, 15);
        $latestId = !empty($notifications) ? (int)max(array_column($notifications, 'id')) : 0;

        echo json_encode([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
            'latest_id' => $latestId
        ]);
        exit;
    }

    public function read(): void
    {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $userId = Auth::id();

        $notif = Notification::markAsRead($id, $userId);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_GET['format']) && $_GET['format'] === 'json');

        if ($isAjax) {
            header('Content-Type: application/json');
            $unreadCount = Notification::getUnreadCount($userId);
            echo json_encode([
                'status' => 'success',
                'notification' => $notif,
                'unread_count' => $unreadCount,
                'removed_id' => $id
            ]);
            exit;
        }

        // Direct redirection to lead details
        if ($notif && !empty($notif['link'])) {
            header('Location: ' . (str_starts_with($notif['link'], 'http') ? $notif['link'] : View::url($notif['link'])));
            exit;
        }

        if ($notif && !empty($notif['lead_id'])) {
            header('Location: ' . View::url('leads/show&id=' . (int)$notif['lead_id']));
            exit;
        }

        header('Location: ' . View::url('dashboard'));
        exit;
    }

    public function markAllRead(): void
    {
        Auth::requireLogin();
        $userId = Auth::id();

        Notification::markAllAsRead($userId);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_GET['format']) && $_GET['format'] === 'json');

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'unread_count' => 0,
                'message' => 'All notifications marked as read'
            ]);
            exit;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? View::url('dashboard');
        header('Location: ' . $referer);
        exit;
    }
}
