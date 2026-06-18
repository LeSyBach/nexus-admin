<?php
require 'db_connect.php';

// Xử lý xóa phản hồi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $firestore->deleteDocument('feedback', $_POST['delete_id']);
        $msg = "Đã xóa phản hồi.";
    } catch (Exception $e) {
        $err = "Lỗi xóa: " . $e->getMessage();
    }
}

// Lấy danh sách phản hồi từ Firestore
$feedbacks = [];
try {
    $feedbacks = $firestore->listDocuments('feedback');
    // Sắp xếp theo thời gian mới nhất
    usort($feedbacks, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
} catch (Exception $e) {
    $err = "Lỗi lấy phản hồi: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phản hồi - Nexus Admin</title>
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
        .feedback-item { border: 1px solid #e0e0e0; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .feedback-item:hover { border-color: #1a73e8; }
        .feedback-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .feedback-email { font-weight: 600; color: #1a73e8; }
        .feedback-time { font-size: 12px; color: #999; }
        .feedback-subject { font-weight: 600; margin-bottom: 6px; }
        .feedback-body { color: #555; line-height: 1.5; }
        .feedback-meta { margin-top: 8px; font-size: 12px; color: #999; }
        .btn { padding: 6px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-delete { background: #ea4335; color: white; }
        .btn:hover { opacity: 0.85; }
        .empty { text-align: center; padding: 40px; color: #999; }
        .stat-row { display: flex; gap: 16px; margin-bottom: 20px; }
        .stat-box { flex: 1; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); text-align: center; }
        .stat-box .number { font-size: 36px; font-weight: bold; color: #1a73e8; }
        .stat-box .label { color: #666; margin-top: 4px; }
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

        <div class="stat-row">
            <div class="stat-box">
                <div class="number"><?= count($feedbacks) ?></div>
                <div class="label">Tổng phản hồi</div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 16px;">Phản hồi từ người dùng</h2>

            <?php if (empty($feedbacks)): ?>
                <div class="empty">
                    <p>Chưa có phản hồi nào.</p>
                    <p style="margin-top: 8px; font-size: 13px;">Phản hồi từ ứng dụng Nexus sẽ hiển thị ở đây.</p>
                </div>
            <?php else: ?>
                <?php foreach ($feedbacks as $fb): ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <span class="feedback-email"><?= htmlspecialchars($fb['email'] ?? 'Ẩn danh') ?></span>
                            <span class="feedback-time"><?= isset($fb['created_at']) ? date('d/m/Y H:i', strtotime($fb['created_at'])) : '' ?></span>
                        </div>
                        <?php if (!empty($fb['subject'])): ?>
                            <div class="feedback-subject"><?= htmlspecialchars($fb['subject']) ?></div>
                        <?php endif; ?>
                        <div class="feedback-body"><?= nl2br(htmlspecialchars($fb['message'] ?? $fb['content'] ?? '')) ?></div>
                        <div class="feedback-meta">
                            UID: <?= htmlspecialchars($fb['uid'] ?? 'N/A') ?>
                            <?php if (!empty($fb['rating'])): ?>
                                | Đánh giá: <?= $fb['rating'] ?>/5
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 10px;">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Xóa phản hồi này?')">
                                <input type="hidden" name="delete_id" value="<?= htmlspecialchars($fb['_id']) ?>">
                                <button type="submit" class="btn btn-delete">Xóa</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
