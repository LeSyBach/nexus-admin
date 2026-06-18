package com.nexus.app

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity

/**
 * ===== HƯỚNG DẪN TÍCH HỢP VÀO MainActivity CỦA BẠN =====
 *
 * Thêm 2 dòng gọi vào onCreate() SAU khi đăng nhập thành công:
 */

class MainActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        // ╔══════════════════════════════════════════════════════╗
        // ║  THÊM 2 DÒNG NÀY VÀO MainActivity.onCreate()       ║
        // ╚══════════════════════════════════════════════════════╝

        // 1. Kiểm tra tài khoản có bị khóa không
        //    Nếu bị khóa → tự động chuyển sang BannedActivity
        AuthCheck.verify(this) {
            // Callback này chạy khi tài khoản OK (không bị khóa)
            // Đặt code khởi tạo app của bạn vào đây
            initApp()
        }

        // 2. Bắt đầu lắng nghe thông báo từ admin
        //    Khi admin gửi thông báo qua trang notifications.php,
        //    user sẽ nhận được push notification trên điện thoại
        NotificationListener.startListening(this)
    }

    private fun initApp() {
        // Code khởi tạo app của bạn ở đây
        // Ví dụ: load fragment chính, kết nối socket, v.v.
    }
}

/**
 * ===== TÓM TẮT CƠ CHẾ HOẠT ĐỘNG =====
 *
 * ┌─────────────────────────────────────────────────────────┐
 * │  BÊN APP (Kotlin)                                       │
 * ├─────────────────────────────────────────────────────────┤
 * │                                                         │
 * │  1. User mở app → MainActivity.onCreate()               │
 * │     └→ AuthCheck.verify()                                │
 * │        └→ Đọc Firestore: users/{uid}.isBanned           │
 * │           ├─ true  → Mở BannedActivity                  │
 * │           │          └→ User gửi feedback qua form       │
 * │           │             └→ Lưu vào Firestore: feedback/  │
 * │           └─ false → Cho phép dùng app bình thường      │
 * │                                                         │
 * │  2. NotificationListener.startListening()                │
 * │     └→ Lắng nghe realtime Firestore: notifications/     │
 * │        └→ Khi có doc mới → Hiển thị notification        │
 * │                                                         │
 * └─────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────┐
 * │  BÊN WEB ADMIN (PHP)                                    │
 * ├─────────────────────────────────────────────────────────┤
 * │                                                         │
 * │  users.php:                                             │
 * │    Nút "Khóa" → auth->disableUser(uid)                  │
 * │                → Firestore: users/{uid}.isBanned = true │
 * │                → (Tùy chọn) Ghi thêm banReason          │
 * │                                                         │
 * │  notifications.php:                                     │
 * │    Form gửi → Firestore: notifications/{auto-id}        │
 * │              → App nhận realtime qua snapshot listener   │
 * │                                                         │
 * │  feedback.php:                                          │
 * │    Hiển thị feedback từ user (bao gồm yêu cầu mở khóa)│
 * │    → Admin xem xét → Mở khóa nếu OK                    │
 * │                                                         │
 * └─────────────────────────────────────────────────────────┘
 */
