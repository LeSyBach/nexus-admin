<?php
/**
 * Nexus Admin — Phản hồi
 * Premium SaaS Dashboard — Deep Dark Theme
 */

require 'db_connect.php';

$msg = null;
$err = null;

// ──────────────────────────────────────────────
// XỬ LÝ XÓA PHẢN HỒI
// ──────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $firestore->deleteDocument('feedback', $_POST['delete_id']);
        $msg = "Đã xóa phản hồi thành công.";
    } catch (Exception $e) {
        $err = "Lỗi xóa: " . $e->getMessage();
    }
}

// ──────────────────────────────────────────────
// LẤY DANH SÁCH PHẢN HỒI
// ──────────────────────────────────────────────

$feedbacks = [];

try {
    $feedbacks = $firestore->listDocuments('feedback');
    usort($feedbacks, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
} catch (Exception $e) {
    $err = "Lỗi lấy phản hồi: " . $e->getMessage();
}

$totalFeedbacks = count($feedbacks);
?>
<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Admin — Phản hồi</title>

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

        .glow-amber { filter: drop-shadow(0 0 8px rgba(251,191,36,0.5)); }

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

        .feedback-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }
        .feedback-card:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(168,85,247,0.2);
        }
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

        <a href="feedback.php" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-purple-400">
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
                <h1 class="text-xl font-bold text-white">Phản hồi</h1>
                <p class="text-xs text-gray-500 mt-0.5">Xem và quản lý phản hồi từ người dùng</p>
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

        <!-- STAT CARD -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng phản hồi</p>
                        <p class="text-3xl font-extrabold text-white mt-2"><?= $totalFeedbacks ?></p>
                        <p class="text-xs text-amber-400 mt-1">Tất cả phản hồi từ người dùng</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center glow-amber">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- FEEDBACK LIST -->
        <div class="space-y-4">
            <?php if (empty($feedbacks)): ?>
                <div class="glass-card rounded-2xl p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-700 mb-4" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-gray-500 text-lg font-medium">Chưa có phản hồi nào</p>
                    <p class="text-gray-600 text-sm mt-1">Phản hồi từ ứng dụng Nexus sẽ hiển thị ở đây.</p>
                </div>
            <?php else: ?>
                <?php foreach ($feedbacks as $fb): ?>
                    <div class="feedback-card rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                <!-- Avatar -->
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-500/30 to-orange-500/30 flex items-center justify-center text-sm font-bold text-amber-300 flex-shrink-0">
                                    <?= strtoupper(substr($fb['email'] ?? '?', 0, 1)) ?>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <!-- Header -->
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <span class="text-sm font-semibold text-white"><?= htmlspecialchars($fb['email'] ?? 'Ẩn danh') ?></span>
                                        <?php if (!empty($fb['rating'])): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-xs font-medium">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <?= $fb['rating'] ?>/5
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-xs text-gray-600">
                                            <?= isset($fb['created_at']) ? date('d/m/Y H:i', strtotime($fb['created_at'])) : '' ?>
                                        </span>
                                    </div>

                                    <!-- Subject -->
                                    <?php if (!empty($fb['subject'])): ?>
                                        <p class="text-sm font-medium text-gray-300 mt-2"><?= htmlspecialchars($fb['subject']) ?></p>
                                    <?php endif; ?>

                                    <!-- Message -->
                                    <p class="text-sm text-gray-400 mt-1 leading-relaxed"><?= nl2br(htmlspecialchars($fb['message'] ?? $fb['content'] ?? '')) ?></p>

                                    <!-- Meta -->
                                    <div class="flex items-center gap-3 mt-3">
                                        <code class="text-[11px] text-gray-600 bg-white/5 px-2 py-0.5 rounded font-mono">
                                            UID: <?= htmlspecialchars($fb['uid'] ?? 'N/A') ?>
                                        </code>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete button -->
                            <form method="POST" onsubmit="return confirm('Xóa phản hồi này?')" class="flex-shrink-0">
                                <input type="hidden" name="delete_id" value="<?= htmlspecialchars($fb['_id']) ?>">
                                <button type="submit" class="p-2 rounded-lg text-gray-600 hover:text-rose-400 hover:bg-rose-500/10 transition" title="Xóa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center text-xs text-gray-600 pb-4">© <?= date('Y') ?> Nexus Admin. All rights reserved.</div>
    </div>
</main>

</body>
</html>
