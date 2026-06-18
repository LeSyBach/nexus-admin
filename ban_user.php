<?php
/**
 * Xử lý khóa/mở khóa user từ index.php
 * Nhận GET request: ban_user.php?uid=xxx&action=ban|unban
 */

require 'db_connect.php';

$uid     = $_GET['uid'] ?? '';
$action  = $_GET['action'] ?? '';

if (!$uid) {
    header('Location: index.php');
    exit;
}

try {
    if ($action === 'ban') {
        $auth->disableUser($uid);
        // Ghi isBanned vào Firestore để app đọc được
        $firestore->updateDocument('users', $uid, [
            'isBanned' => true,
            'bannedAt' => date('c'),
        ]);
    } elseif ($action === 'unban') {
        $auth->enableUser($uid);
        // Xóa isBanned trên Firestore
        $firestore->updateDocument('users', $uid, [
            'isBanned' => false,
            'bannedAt' => null,
        ]);
    }
} catch (Exception $e) {
    // Bỏ qua lỗi, quay về dashboard
}

header('Location: index.php');
exit;
