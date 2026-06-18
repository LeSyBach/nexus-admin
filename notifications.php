<?php
require 'db_connect.php';

// Xử lý gửi thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');
    $target = $_POST['target'] ?? 'all';

    if ($title && $body) {
        try {
            $notification = [
                'title'      => $title,
                'body'       => $body,
                'target'     => $target,
                'created_at' => date('c'),
                'status'     => 'sent',
            ];

            $firestore->addDocument('notifications', $notification);
            $msg = "Đã gửi thông báo thành công!";
        } catch (Exception $e) {
            $err = "Lỗi gửi thông báo: " . $e->getMessage();
        }
    } else {
        $err = "Vui lòng nhập đầy đủ tiêu đề và nội dung.";
    }
}

// Lấy lịch sử thông báo
$notifications = [];
try {
    $notifications = $firestore->listDocuments('notifications');
    usort($notifications, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
} catch (Exception $e) {
    $err = "Lỗi lấy lịch sử: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo - Nexus Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; color: #333; }
        .navbar { background: #1a73e8; color: white; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; }
        .navbar h1 { font-size: 20px; }
        .navbar a { color: white; text-decoration: none; margin-left: 20px; opacity: 0.9; }
        .navbar a:hover { opacity: 1; }
        .container { max-width: 1200px; margin: 24px auto; padding: 0 16px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); padding: 24px; margin-bottom: 20px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-success { background: #e6f4ea; color: #137333; border: 1px solid #ceead6; }
        .alert-error { background: #fce8e6; color: #c5221f; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #555; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #1a73e8; }
        .btn { padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-primary { background: #1a73e8; color: white; }
        .btn-delete { background: #ea4335; color: white; padding: 6px 14px; font-size: 13px; }
        .btn:hover { opacity: 0.85; }
        .notif-item { border: 1px solid #e0e0e0; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .notif-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .notif-title { font-weight: 600; font-size: 16px; }
        .notif-time { font-size: 12px; color: #999; }
        .notif-body { color: #555; line-height: 1.5; }
        .notif-meta { margin-top: 8px; font-size: 12px; color: #999; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-all { background: #e8f0fe; color: #1a73e8; }
        .empty { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Nexus Admin</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="users.php">Người dùng</a>
            <a href="feedback.php">Phản hồi</a>
            <a href="notifications.php">Thông báo</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($msg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if (isset($err)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <!-- Form gửi thông báo -->
        <div class="card">
            <h2 style="margin-bottom: 16px;">Gửi thông báo hệ thống</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Tiêu đề</label>
                    <input type="text" name="title" placeholder="Nhập tiêu đề thông báo..." required>
                </div>
                <div class="form-group">
                    <label>Nội dung</label>
                    <textarea name="body" placeholder="Nhập nội dung thông báo..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Gửi đến</label>
                    <select name="target">
                        <option value="all">Tất cả người dùng</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Gửi thông báo</button>
            </form>
        </div>

        <!-- Lịch sử thông báo -->
        <div class="card">
            <h2 style="margin-bottom: 16px;">Lịch sử thông báo</h2>

            <?php if (empty($notifications)): ?>
                <div class="empty">
                    <p>Chưa gửi thông báo nào.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notif-item">
                        <div class="notif-header">
                            <span class="notif-title"><?= htmlspecialchars($notif['title'] ?? '') ?></span>
                            <span class="notif-time"><?= isset($notif['created_at']) ? date('d/m/Y H:i', strtotime($notif['created_at'])) : '' ?></span>
                        </div>
                        <div class="notif-body"><?= nl2br(htmlspecialchars($notif['body'] ?? '')) ?></div>
                        <div class="notif-meta">
                            <span class="badge badge-all"><?= htmlspecialchars($notif['target'] ?? 'all') ?></span>
                            | Trạng thái: <?= htmlspecialchars($notif['status'] ?? 'sent') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
