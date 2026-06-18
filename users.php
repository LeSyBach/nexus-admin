<?php
/**
 * Nexus Admin — Quản lý người dùng
 * Premium SaaS Dashboard — Deep Dark Theme
 */

require 'db_connect.php';

// ──────────────────────────────────────────────
// XỬ LÝ HÀNH ĐỘNG
// ──────────────────────────────────────────────

$msg = null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = $_POST['uid'] ?? '';

    if ($uid) {
        try {
            $user = $auth->getUser($uid);

            switch ($action) {
                case 'lock':
                    $auth->disableUser($uid);
                    // Ghi isBanned vào Firestore để app đọc được
                    $firestore->updateDocument('users', $uid, [
                        'isBanned' => true,
                        'bannedAt' => date('c'),
                    ]);
                    $msg = "Đã khóa tài khoản: {$user->email}";
                    break;
                case 'unlock':
                    $auth->enableUser($uid);
                    // Xóa isBanned trên Firestore
                    $firestore->updateDocument('users', $uid, [
                        'isBanned' => false,
                        'bannedAt' => null,
                    ]);
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

// ──────────────────────────────────────────────
// LẤY DANH SÁCH USER
// ──────────────────────────────────────────────

$users = [];

try {
    $users = iterator_to_array($auth->listUsers($defaultMaxResults = 1000));
} catch (Exception $e) {
    $err = "Lỗi lấy danh sách: " . $e->getMessage();
}

$totalUsers  = count($users);
$activeUsers = count(array_filter($users, fn($u) => !$u->disabled));
$bannedUsers = count(array_filter($users, fn($u) => $u->disabled));
?>
<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Admin — Người dùng</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwindcss.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        surface: { DEFAULT: '#0b1121', deep: '#060813', card: 'rgba(255,255,255,0.04)' }
                    }
                }
            }
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #060813; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 3px; }

        .glow-purple { filter: drop-shadow(0 0 8px rgba(168,85,247,0.5)); }
        .glow-cyan   { filter: drop-shadow(0 0 8px rgba(34,211,238,0.5)); }
        .glow-rose   { filter: drop-shadow(0 0 8px rgba(251,113,133,0.5)); }

        @keyframes neon-pulse {
            0%, 100% { box-shadow: 0 0 4px #f43f5e, 0 0 8px #f43f5e; }
            50%      { box-shadow: 0 0 8px #f43f5e, 0 0 16px #f43f5e; }
        }
        .neon-dot { width: 8px; height: 8px; background: #f43f5e; border-radius: 50%; animation: neon-pulse 2s ease-in-out infinite; }

        .glass-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(168,85,247,0.1); color: #c084fc; }
        .sidebar-link.active { border-left: 3px solid #a855f7; }
    </style>
</head>
<body class="text-gray-300 min-h-screen flex">

<!-- ═══ SIDEBAR ═══ -->
<aside class="w-64 min-h-screen border-r border-white/5 bg-surface-deep flex flex-col fixed top-0 left-0 z-30">
    <div class="px-6 py-6 flex items-center gap-3 border-b border-white/5">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-cyan-400 flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <span class="text-lg font-bold text-white tracking-wide">Nexus<span class="text-purple-400">.</span></span>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
        <p class="px-3 mb-3 text-[10px] font-semibold uppercase tracking-widest text-gray-500">Menu chính</p>

        <a href="index.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-gray-200">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/>
            </svg>
            Dashboard
        </a>

        <a href="users.php" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-purple-400">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Người dùng
        </a>

        <a href="feedback.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-gray-200">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Phản hồi
        </a>

        <a href="notifications.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-gray-200">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Thông báo
        </a>

        <p class="px-3 mt-6 mb-3 text-[10px] font-semibold uppercase tracking-widest text-gray-500">Hệ thống</p>

        <a href="#" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-gray-200">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Cài đặt
        </a>
    </nav>

    <div class="px-4 py-4 border-t border-white/5">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-cyan-400 flex items-center justify-center text-white text-sm font-bold">A</div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">Admin</p>
                <p class="text-xs text-gray-500">Super Admin</p>
            </div>
        </div>
    </div>
</aside>

<!-- ═══ MAIN CONTENT ═══ -->
<main class="flex-1 ml-64 min-h-screen">

    <!-- HEADER -->
    <header class="sticky top-0 z-20 backdrop-blur-xl bg-surface-deep/80 border-b border-white/5">
        <div class="flex items-center justify-between px-8 py-4">
            <div>
                <h1 class="text-xl font-bold text-white">Quản lý người dùng</h1>
                <p class="text-xs text-gray-500 mt-0.5">Khóa, mở khóa và quản lý tài khoản</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" placeholder="Tìm kiếm..."
                           class="w-64 pl-10 pr-4 py-2 rounded-full bg-white/5 border border-white/5 text-sm text-gray-300 placeholder-gray-600 focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/20 transition">
                </div>
                <button class="relative p-2 rounded-xl hover:bg-white/5 transition">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute top-1.5 right-1.5 neon-dot"></span>
                </button>
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-cyan-400 flex items-center justify-center text-white text-sm font-bold cursor-pointer ring-2 ring-purple-500/20">A</div>
            </div>
        </div>
    </header>

    <div class="p-8 space-y-8">

        <!-- Alert -->
        <?php if ($msg): ?>
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <span><?= htmlspecialchars($err) ?></span>
            </div>
        <?php endif; ?>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng người dùng</p>
                        <p class="text-3xl font-extrabold text-white mt-2"><?= $totalUsers ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center glow-purple">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Đang hoạt động</p>
                        <p class="text-3xl font-extrabold text-white mt-2"><?= $activeUsers ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center glow-cyan">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bị khóa</p>
                        <p class="text-3xl font-extrabold text-white mt-2"><?= $bannedUsers ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center glow-rose">
                        <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEARCH + TABLE -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-white">Danh sách người dùng</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Quản lý tất cả tài khoản Firebase</p>
                </div>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="searchInput" placeholder="Tìm theo tên, email, UID..."
                           class="pl-10 pr-4 py-2 w-64 rounded-xl bg-white/5 border border-white/5 text-sm text-gray-300 placeholder-gray-600 focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/20 transition"
                           onkeyup="filterTable()">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="userTable">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tên</th>
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Email</th>
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">UID</th>
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Trạng thái</th>
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Ngày tạo</th>
                            <th class="text-right px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-600">Chưa có người dùng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $i => $user): ?>
                                <?php
                                    $banned = $user->disabled;
                                    $statusText  = $banned ? 'Bị khóa' : 'Hoạt động';
                                    $statusClass = $banned
                                        ? 'bg-rose-500/10 text-rose-400 ring-rose-500/20'
                                        : 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20';
                                    $createdAt = $user->metadata->createdAt
                                        ? date('d/m/Y H:i', $user->metadata->createdAt->getTimestamp())
                                        : 'N/A';
                                ?>
                                <tr class="hover:bg-white/[0.02] transition">
                                    <td class="px-6 py-3.5 text-gray-500 font-medium"><?= $i + 1 ?></td>
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <?php if ($user->photoUrl): ?>
                                                <img src="<?= htmlspecialchars($user->photoUrl) ?>" alt="" class="w-8 h-8 rounded-full object-cover ring-1 ring-white/10">
                                            <?php else: ?>
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500/30 to-cyan-500/30 flex items-center justify-center text-xs font-bold text-white">
                                                    <?= strtoupper(substr($user->displayName ?? $user->email ?? '?', 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <span class="text-gray-200 font-medium"><?= htmlspecialchars($user->displayName ?? 'Chưa đặt tên') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5 text-gray-400 text-sm"><?= htmlspecialchars($user->email ?? 'N/A') ?></td>
                                    <td class="px-6 py-3.5">
                                        <code class="text-xs text-gray-500 bg-white/5 px-2 py-0.5 rounded font-mono">
                                            <?= htmlspecialchars(substr($user->uid, 0, 16)) ?><?= strlen($user->uid) > 16 ? '...' : '' ?>
                                        </code>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-gray-400 text-xs"><?= $createdAt ?></td>
                                    <td class="px-6 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <?php if ($banned): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="uid" value="<?= $user->uid ?>">
                                                    <input type="hidden" name="action" value="unlock">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs font-medium hover:bg-emerald-500/20 transition">
                                                        Mở khóa
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="uid" value="<?= $user->uid ?>">
                                                    <input type="hidden" name="action" value="lock">
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-400 text-xs font-medium hover:bg-amber-500/20 transition">
                                                        Khóa
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Xác nhận XÓA vĩnh viễn tài khoản này?')">
                                                <input type="hidden" name="uid" value="<?= $user->uid ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 text-xs font-medium hover:bg-rose-500/20 transition">
                                                    Xóa
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center text-xs text-gray-600 pb-4">© <?= date('Y') ?> Nexus Admin. All rights reserved.</div>
    </div>
</main>

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
