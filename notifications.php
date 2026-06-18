<?php
/**
 * Nexus Admin — Thông báo hệ thống
 * Premium SaaS Dashboard — Deep Dark Theme
 */

require 'db_connect.php';

$msg = null;
$err = null;

// ──────────────────────────────────────────────
// XỬ LÝ GỬI THÔNG BÁO
// ──────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
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

// ──────────────────────────────────────────────
// LẤY LỊCH SỬ THÔNG BÁO
// ──────────────────────────────────────────────

$notifications = [];

try {
    $notifications = $firestore->listDocuments('notifications');
    usort($notifications, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
} catch (Exception $e) {
    $err = "Lỗi lấy lịch sử: " . $e->getMessage();
}

$totalNotifs = count($notifications);
?>
<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Admin — Thông báo</title>

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

        .glow-cyan { filter: drop-shadow(0 0 8px rgba(34,211,238,0.5)); }

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

        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(168,85,247,0.1); color: #c084fc; }
        .sidebar-link.active { border-left: 3px solid #a855f7; }

        .notif-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }
        .notif-card:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(34,211,238,0.2);
        }

        /* Form inputs */
        .form-input {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            color: #e5e7eb;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: rgba(168,85,247,0.5);
            box-shadow: 0 0 0 3px rgba(168,85,247,0.1);
        }
        .form-input::placeholder { color: #4b5563; }
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

        <a href="users.php" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-gray-200">
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

        <a href="notifications.php" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-purple-400">
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
                <h1 class="text-xl font-bold text-white">Thông báo hệ thống</h1>
                <p class="text-xs text-gray-500 mt-0.5">Gửi và quản lý thông báo đến người dùng</p>
            </div>
            <div class="flex items-center gap-4">
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ═══ FORM GỬI THÔNG BÁO ═══ -->
            <div class="lg:col-span-2">
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-white">Soạn thông báo mới</h3>
                            <p class="text-xs text-gray-500">Gửi đến tất cả người dùng Nexus</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Tiêu đề</label>
                            <input type="text" name="title" placeholder="Nhập tiêu đề thông báo..." required
                                   class="form-input w-full px-4 py-3 rounded-xl text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Nội dung</label>
                            <textarea name="body" placeholder="Nhập nội dung thông báo..." required rows="4"
                                      class="form-input w-full px-4 py-3 rounded-xl text-sm resize-y"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Gửi đến</label>
                            <select name="target" class="form-input w-full px-4 py-3 rounded-xl text-sm appearance-none cursor-pointer">
                                <option value="all" class="bg-[#0b1121]">Tất cả người dùng</option>
                            </select>
                        </div>

                        <button type="submit"
                                class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gradient-to-r from-purple-500 to-cyan-500 text-white text-sm font-semibold hover:opacity-90 transition shadow-lg shadow-purple-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Gửi thông báo
                        </button>
                    </form>
                </div>
            </div>

            <!-- ═══ STATS SIDEBAR ═══ -->
            <div class="space-y-5">
                <div class="glass-card rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Đã gửi</p>
                            <p class="text-3xl font-extrabold text-white mt-2"><?= $totalNotifs ?></p>
                            <p class="text-xs text-cyan-400 mt-1">Tổng thông báo</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center glow-cyan">
                            <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Mẹo</p>
                    <ul class="space-y-2 text-xs text-gray-400">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-purple-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Thông báo sẽ được gửi đến tất cả người dùng đang hoạt động</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-purple-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Nội dung ngắn gọn, rõ ràng sẽ hiệu quả hơn</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- ═══ LỊCH SỬ THÔNG BÁO ═══ -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-white/5">
                <h3 class="text-base font-semibold text-white">Lịch sử thông báo</h3>
                <p class="text-xs text-gray-500 mt-0.5">Các thông báo đã gửi trước đó</p>
            </div>

            <div class="p-6 space-y-3">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-10">
                        <svg class="w-14 h-14 mx-auto text-gray-700 mb-3" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <p class="text-gray-500 font-medium">Chưa gửi thông báo nào</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notif-card rounded-xl p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-cyan-500/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3 flex-wrap">
                                            <h4 class="text-sm font-semibold text-white"><?= htmlspecialchars($notif['title'] ?? '') ?></h4>
                                            <span class="px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-400 text-[11px] font-medium">
                                                <?= htmlspecialchars($notif['target'] ?? 'all') ?>
                                            </span>
                                            <span class="text-[11px] text-gray-600">
                                                <?= isset($notif['created_at']) ? date('d/m/Y H:i', strtotime($notif['created_at'])) : '' ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-400 mt-1.5 leading-relaxed"><?= nl2br(htmlspecialchars($notif['body'] ?? '')) ?></p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[11px] font-medium flex-shrink-0">
                                    <?= htmlspecialchars($notif['status'] ?? 'sent') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center text-xs text-gray-600 pb-4">© <?= date('Y') ?> Nexus Admin. All rights reserved.</div>
    </div>
</main>

</body>
</html>
