<?php
require 'db_connect.php';

// Xử lý hành động từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid = $_POST['uid'] ?? '';

    if ($uid) {
        try {
            $user = $auth->getUser($uid);

            switch ($action) {
                case 'lock':
                    $auth->disableUser($uid);
                    $msg = "Đã khóa tài khoản: {$user->email}";
                    break;
                case 'unlock':
                    $auth->enableUser($uid);
                    $msg = "Đã mở khóa tài khoản: {$user->email}";
                    break;
                case 'delete':
                    $auth->deleteUser($uid);
                    $msg = "Đã xóa tài khoản: {$user->email}";
                    break;
            }
        } catch (Exception $e) {
            $err = "Lỗi: " . $e->getMessage();
        }
    }
}

// Lấy danh sách user
try {
    $users = iterator_to_array($auth->listUsers($defaultMaxResults = 1000));
} catch (Exception $e) {
    $users = [];
    $err = "Lỗi lấy danh sách: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Nexus Admin</title>
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
        .stats { display: flex; gap: 16px; margin-bottom: 20px; }
        .stat-box { flex: 1; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); text-align: center; }
        .stat-box .number { font-size: 36px; font-weight: bold; color: #1a73e8; }
        .stat-box .label { color: #666; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: 600; color: #555; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-active { background: #e6f4ea; color: #137333; }
        .badge-locked { background: #fce8e6; color: #c5221f; }
        .btn { padding: 6px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; margin-right: 4px; }
        .btn-lock { background: #fbbc04; color: #333; }
        .btn-unlock { background: #34a853; color: white; }
        .btn-delete { background: #ea4335; color: white; }
        .btn:hover { opacity: 0.85; }
        .search-box { width: 100%; padding: 10px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; margin-bottom: 16px; }
        .search-box:focus { outline: none; border-color: #1a73e8; }
        .inline-form { display: inline; }
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

        <div class="stats">
            <div class="stat-box">
                <div class="number"><?= count($users) ?></div>
                <div class="label">Tổng người dùng</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= count(array_filter($users, fn($u) => !$u->disabled)) ?></div>
                <div class="label">Đang hoạt động</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= count(array_filter($users, fn($u) => $u->disabled)) ?></div>
                <div class="label">Đã khóa</div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 16px;">Danh sách người dùng</h2>

            <input type="text" class="search-box" id="searchInput" placeholder="Tìm kiếm theo email hoặc UID..." onkeyup="filterTable()">

            <table id="userTable">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>UID</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user->email ?? 'N/A') ?></td>
                        <td style="font-family: monospace; font-size: 12px;"><?= $user->uid ?></td>
                        <td>
                            <?php if ($user->disabled): ?>
                                <span class="badge badge-locked">Đã khóa</span>
                            <?php else: ?>
                                <span class="badge badge-active">Hoạt động</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user->metadata->createdAt ? date('d/m/Y H:i', $user->metadata->createdAt->getTimestamp()) : 'N/A' ?></td>
                        <td>
                            <?php if ($user->disabled): ?>
                                <form class="inline-form" method="POST">
                                    <input type="hidden" name="uid" value="<?= $user->uid ?>">
                                    <input type="hidden" name="action" value="unlock">
                                    <button type="submit" class="btn btn-unlock">Mở khóa</button>
                                </form>
                            <?php else: ?>
                                <form class="inline-form" method="POST">
                                    <input type="hidden" name="uid" value="<?= $user->uid ?>">
                                    <input type="hidden" name="action" value="lock">
                                    <button type="submit" class="btn btn-lock">Khóa</button>
                                </form>
                            <?php endif; ?>
                            <form class="inline-form" method="POST" onsubmit="return confirm('Xác nhận xóa tài khoản này?')">
                                <input type="hidden" name="uid" value="<?= $user->uid ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-delete">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function filterTable() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#userTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    }
    </script>
</body>
</html>
