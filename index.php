<?php
/**
 * Nexus Admin Dashboard
 * Premium SaaS Dashboard — Deep Dark Theme
 */

require 'db_connect.php';

// ──────────────────────────────────────────────
// 1. TRUY VẤN DỮ LIỆU TỪ FIREBASE
// ──────────────────────────────────────────────

$users      = [];
$feedbacks  = [];
$errAuth    = null;

// Lấy danh sách user từ Firebase Auth
try {
    $users = iterator_to_array($auth->listUsers($defaultMaxResults = 1000));
} catch (Exception $e) {
    $errAuth = $e->getMessage();
}

// Lấy feedback từ Firestore
try {
    $feedbacks = $firestore->listDocuments('feedback');
} catch (Exception $e) {
    // Firestore có thể chưa có dữ liệu
}

// ──────────────────────────────────────────────
// 2. XỬ LÝ SỐ LIỆU
// ──────────────────────────────────────────────

$totalUsers   = count($users);
$activeUsers  = 0;
$bannedUsers  = 0;

foreach ($users as $u) {
    if ($u->disabled) {
        $bannedUsers++;
    } else {
        $activeUsers++;
    }
}

$totalFeedbacks = count($feedbacks);

// 10 user mới nhất (sắp xếp theo createdAt giảm dần)
$sortedUsers = $users;
usort($sortedUsers, function ($a, $b) {
    $ta = $a->metadata->createdAt ? $a->metadata->createdAt->getTimestamp() : 0;
    $tb = $b->metadata->createdAt ? $b->metadata->createdAt->getTimestamp() : 0;
    return $tb - $ta;
});
$latestUsers = array_slice($sortedUsers, 0, 10);

// ──────────────────────────────────────────────
// 3. CHUẨN BỊ DỮ LIỆU CHO BIỂU ĐỒ
// ──────────────────────────────────────────────

$chartSeries = [$activeUsers, $bannedUsers];
$chartLabels = ['Hoạt động', 'Bị khóa'];
$chartJSON   = json_encode($chartSeries);
$labelsJSON  = json_encode($chartLabels);
?>
<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Admin — Dashboard</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwindcss.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        surface: {
                            DEFAULT: '#0b1121',
                            deep:    '#060813',
                            card:    'rgba(255,255,255,0.04)',
                        }
                    }
                }
            }
        }
    </script>

    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #060813; }

        /* Scrollbar tối */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }

        /* Glow effects */
        .glow-purple { filter: drop-shadow(0 0 8px rgba(168,85,247,0.5)); }
        .glow-cyan   { filter: drop-shadow(0 0 8px rgba(34,211,238,0.5)); }
        .glow-amber  { filter: drop-shadow(0 0 8px rgba(251,191,36,0.5)); }
        .glow-rose   { filter: drop-shadow(0 0 8px rgba(251,113,133,0.5)); }

        /* Neon dot pulse */
        @keyframes neon-pulse {
            0%, 100% { box-shadow: 0 0 4px #f43f5e, 0 0 8px #f43f5e; }
            50%      { box-shadow: 0 0 8px #f43f5e, 0 0 16px #f43f5e; }
        }
        .neon-dot {
            width: 8px; height: 8px;
            background: #f43f5e;
            border-radius: 50%;
            animation: neon-pulse 2s ease-in-out infinite;
        }

        /* Glass card hover */
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

        /* Sidebar link */
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(168,85,247,0.1);
            color: #c084fc;
        }
        .sidebar-link.active { border-left: 3px solid #a855f7; }
    </style>
</head>
<body class="text-gray-300 min-h-screen flex">

<!-- ═══════════════════════════════════════════
     SIDEBAR
     ═══════════════════════════════════════════ -->
<aside class="w-64 min-h-screen border-r border-white/5 bg-surface-deep flex flex-col fixed top-0 left-0 z-30">

    <!-- Logo -->
    <div class="px-6 py-6 flex items-center gap-3 border-b border-white/5">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-cyan-400 flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <span class="text-lg font-bold text-white tracking-wide">Nexus<span class="text-purple-400">.</span></span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
        <p class="px-3 mb-3 text-[10px] font-semibold uppercase tracking-widest text-gray-500">Menu chính</p>

        <a href="index.php" class="sidebar-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-purple-400">
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

    <!-- User info -->
    <div class="px-4 py-4 border-t border-white/5">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-cyan-400 flex items-center justify-center text-white text-sm font-bold">A</div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">Admin</p>
                <p class="text-xs text-gray-500">Super Admin</p>
            </div>
            <svg class="w-4 h-4 text-gray-500 cursor-pointer hover:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
        </div>
    </div>
</aside>

<!-- ═══════════════════════════════════════════
     MAIN CONTENT
     ═══════════════════════════════════════════ -->
<main class="flex-1 ml-64 min-h-screen">

    <!-- ── HEADER ── -->
    <header class="sticky top-0 z-20 backdrop-blur-xl bg-surface-deep/80 border-b border-white/5">
        <div class="flex items-center justify-between px-8 py-4">
            <div>
                <h1 class="text-xl font-bold text-white">Dashboard</h1>
                <p class="text-xs text-gray-500 mt-0.5">Chào mừng trở lại, Admin 👋</p>
            </div>

            <div class="flex items-center gap-4">
                <!-- Search -->
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" placeholder="Tìm kiếm..."
                           class="w-64 pl-10 pr-4 py-2 rounded-full bg-white/5 border border-white/5 text-sm text-gray-300 placeholder-gray-600 focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/20 transition">
                </div>

                <!-- Notification bell -->
                <button class="relative p-2 rounded-xl hover:bg-white/5 transition">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute top-1.5 right-1.5 neon-dot"></span>
                </button>

                <!-- Avatar -->
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-cyan-400 flex items-center justify-center text-white text-sm font-bold cursor-pointer ring-2 ring-purple-500/20 hover:ring-purple-500/40 transition">
                    A
                </div>
            </div>
        </div>
    </header>

    <!-- ── CONTENT ── -->
    <div class="p-8 space-y-8">

        <!-- Error Alert -->
        <?php if ($errAuth): ?>
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span>Lỗi Firebase Auth: <?= htmlspecialchars($errAuth) ?></span>
            </div>
        <?php endif; ?>

        <!-- ════ STAT CARDS ════ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

            <!-- Card 1: Tổng User -->
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng người dùng</p>
                        <p class="text-3xl font-extrabold text-white mt-2"><?= $totalUsers ?></p>
                        <p class="text-xs text-emerald-400 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            Tất cả tài khoản
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center glow-purple">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 2: Đang hoạt động -->
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Đang hoạt động</p>
                        <p class="text-3xl font-extrabold text-white mt-2"><?= $activeUsers ?></p>
                        <p class="text-xs text-emerald-400 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            Tài khoản hợp lệ
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center glow-cyan">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 3: Bị khóa -->
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bị khóa</p>
                        <p class="text-3xl font-extrabold text-white mt-2"><?= $bannedUsers ?></p>
                        <p class="text-xs text-rose-400 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Tài khoản bị cấm
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center glow-rose">
                        <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 4: Phản hồi -->
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phản hồi</p>
                        <p class="text-3xl font-extrabold text-white mt-2"><?= $totalFeedbacks ?></p>
                        <p class="text-xs text-amber-400 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Từ người dùng
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center glow-amber">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════ CHART + QUICK STATS ════ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Donut Chart -->
            <div class="lg:col-span-2 glass-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-white">Trạng thái tài khoản</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Phân bố Hoạt động / Bị khóa</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-purple-500/10 text-purple-400 text-xs font-medium">Realtime</span>
                </div>
                <div id="donutChart" class="flex justify-center"></div>
            </div>

            <!-- Quick Info -->
            <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-base font-semibold text-white mb-1">Tổng quan nhanh</h3>
                    <p class="text-xs text-gray-500 mb-6">Cập nhật lần cuối: Hôm nay</p>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-white/5">
                            <span class="text-sm text-gray-400">Tỉ lệ hoạt động</span>
                            <span class="text-sm font-semibold text-emerald-400">
                                <?= $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0 ?>%
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-white/5">
                            <span class="text-sm text-gray-400">Tỉ lệ bị khóa</span>
                            <span class="text-sm font-semibold text-rose-400">
                                <?= $totalUsers > 0 ? round(($bannedUsers / $totalUsers) * 100, 1) : 0 ?>%
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-white/5">
                            <span class="text-sm text-gray-400">Feedback / User</span>
                            <span class="text-sm font-semibold text-amber-400">
                                <?= $totalUsers > 0 ? round($totalFeedbacks / $totalUsers, 1) : 0 ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <span class="text-sm text-gray-400">Tổng phản hồi</span>
                            <span class="text-sm font-semibold text-cyan-400"><?= $totalFeedbacks ?></span>
                        </div>
                    </div>
                </div>

                <a href="users.php" class="mt-6 flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-purple-500/10 text-purple-400 text-sm font-medium hover:bg-purple-500/20 transition">
                    Xem tất cả người dùng
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>

        <!-- ════ DATA TABLE ════ -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-white">Người dùng mới nhất</h3>
                    <p class="text-xs text-gray-500 mt-0.5">10 tài khoản đăng ký gần đây</p>
                </div>
                <a href="users.php" class="text-xs text-purple-400 hover:text-purple-300 font-medium transition">Xem tất cả →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">#</th>
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Email</th>
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">UID</th>
                            <th class="text-left px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Trạng thái</th>
                            <th class="text-right px-6 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        <?php if (empty($latestUsers)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-600">Chưa có người dùng nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latestUsers as $i => $user): ?>
                                <?php
                                    $uid    = $user->uid;
                                    $email  = $user->email ?: 'Chưa có email';
                                    $banned = $user->disabled;

                                    $statusText  = $banned ? 'Bị khóa' : 'Hoạt động';
                                    $statusClass = $banned
                                        ? 'bg-rose-500/10 text-rose-400 ring-rose-500/20'
                                        : 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20';

                                    $actionLink  = $banned
                                        ? "ban_user.php?uid={$uid}&action=unban"
                                        : "ban_user.php?uid={$uid}&action=ban";
                                    $actionText  = $banned ? 'Mở khóa' : 'Khóa';
                                    $actionClass = $banned
                                        ? 'text-emerald-400 hover:text-emerald-300'
                                        : 'text-rose-400 hover:text-rose-300';
                                ?>
                                <tr class="hover:bg-white/[0.02] transition">
                                    <td class="px-6 py-3.5 text-gray-500 font-medium"><?= $i + 1 ?></td>
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500/30 to-cyan-500/30 flex items-center justify-center text-xs font-bold text-white">
                                                <?= strtoupper(substr($email, 0, 1)) ?>
                                            </div>
                                            <span class="text-gray-200"><?= htmlspecialchars($email) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <code class="text-xs text-gray-500 bg-white/5 px-2 py-0.5 rounded font-mono">
                                            <?= htmlspecialchars(substr($uid, 0, 16)) ?><?= strlen($uid) > 16 ? '...' : '' ?>
                                        </code>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-right">
                                        <a href="<?= $actionLink ?>"
                                           class="text-xs font-medium <?= $actionClass ?> transition hover:underline"
                                           onclick="return confirm('Bạn có chắc muốn <?= $banned ? 'mở khóa' : 'khóa' ?> tài khoản này?')">
                                            <?= $actionText ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-600 pb-4">
            © <?= date('Y') ?> Nexus Admin. All rights reserved.
        </div>

    </div>
</main>

<!-- ═══════════════════════════════════════════
     APEXCHARTS INIT
     ═══════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const series  = <?= $chartJSON ?>;
    const labels  = <?= $labelsJSON ?>;

    const hasData = series.some(v => v > 0);

    const options = {
        series: hasData ? series : [1, 0],
        labels: labels,
        chart: {
            type: 'donut',
            height: 300,
            background: 'transparent',
        },
        colors: ['#34d399', '#fb7185'],
        stroke: { show: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 600,
                            color: '#e5e7eb',
                        },
                        value: {
                            show: true,
                            fontSize: '28px',
                            fontWeight: 800,
                            color: '#ffffff',
                            formatter: function (val) { return val; }
                        },
                        total: {
                            show: true,
                            label: 'Tổng cộng',
                            fontSize: '12px',
                            color: '#6b7280',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        legend: {
            position: 'bottom',
            fontSize: '13px',
            fontWeight: 500,
            labels: { colors: '#9ca3af' },
            markers: { width: 10, height: 10, radius: 5 },
            itemMargin: { horizontal: 16, vertical: 8 }
        },
        tooltip: {
            theme: 'dark',
            style: { fontSize: '13px' },
            y: { formatter: function (val) { return val + ' người dùng'; } }
        },
        grid: { show: false },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: { height: 260 },
                legend: { position: 'bottom' }
            }
        }]
    };

    const chart = new ApexCharts(document.querySelector('#donutChart'), options);
    chart.render();
});
</script>

</body>
</html>
