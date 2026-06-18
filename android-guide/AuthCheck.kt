package com.nexus.app

import android.content.Context
import android.content.Intent
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore

/**
 * Kiểm tra trạng thái tài khoản mỗi khi mở app hoặc đăng nhập thành công.
 *
 * CÁCH DỤNG:
 * Gọi AuthCheck.verify(this) trong MainActivity.onCreate() SAU khi đăng nhập thành công.
 *
 * Flow:
 * 1. Lấy UID của user hiện tại
 * 2. Đọc document "users/{uid}" từ Firestore
 * 3. Nếu field "isBanned" == true → chuyển sang BannedActivity
 * 4. Nếu không → cho phép tiếp tục dùng app
 */
object AuthCheck {

    fun verify(context: Context, onAllowed: () -> Unit) {
        val auth = FirebaseAuth.getInstance()
        val user = auth.currentUser

        if (user == null) {
            // Chưa đăng nhập → về Login
            context.startActivity(Intent(context, LoginActivity::class.java))
            (context as? android.app.Activity)?.finish()
            return
        }

        val db = FirebaseFirestore.getInstance()

        // Cách 1: Kiểm tra từ Firestore (isBanned field)
        db.collection("users").document(user.uid).get()
            .addOnSuccessListener { document ->
                val isBanned = document.getBoolean("isBanned") ?: false

                if (isBanned) {
                    // Bị khóa → chuyển sang màn hình khóa
                    context.startActivity(Intent(context, BannedActivity::class.java))
                    (context as? android.app.Activity)?.finish()
                } else {
                    // OK → cho phép tiếp tục
                    onAllowed()
                }
            }
            .addOnFailureListener {
                // Lỗi network → cho phép tạm thời, lần sau kiểm tra lại
                onAllowed()
            }

        // Cách 2 (tùy chọn): Kiểm tra từ Firebase Auth disabled status
        // Hữu ích nếu bạn dùng auth->disableUser() từ admin
        user.reload().addOnCompleteListener { task ->
            if (task.isSuccessful) {
                // Kiểm tra lại sau khi reload
                if (user.isDisabled) {
                    context.startActivity(Intent(context, BannedActivity::class.java))
                    (context as? android.app.Activity)?.finish()
                }
            }
        }
    }
}
