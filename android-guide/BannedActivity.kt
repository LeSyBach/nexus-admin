package com.nexus.app

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore

/**
 * Màn hình hiển thị khi tài khoản bị khóa (disabled = true)
 * - Thông báo cho user biết tài khoản đã bị khóa
 * - Cho phép gửi phản hồi lên admin để xem xét
 */
class BannedActivity : AppCompatActivity() {

    private lateinit var auth: FirebaseAuth
    private lateinit var db: FirebaseFirestore

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_banned)

        auth = FirebaseAuth.getInstance()
        db = FirebaseFirestore.getInstance()

        val tvTitle = findViewById<TextView>(R.id.tvBannedTitle)
        val tvMessage = findViewById<TextView>(R.id.tvBannedMessage)
        val etFeedback = findViewById<EditText>(R.id.etFeedback)
        val btnSendFeedback = findViewById<Button>(R.id.btnSendFeedback)
        val btnLogout = findViewById<Button>(R.id.btnLogout)

        // Lấy lý do khóa từ Firestore (nếu có)
        val uid = auth.currentUser?.uid ?: return
        db.collection("users").document(uid).get()
            .addOnSuccessListener { doc ->
                val reason = doc.getString("banReason")
                if (!reason.isNullOrEmpty()) {
                    tvMessage.text = "Lý do: $reason\n\nNếu bạn cho rằng đây là nhầm lẫn, hãy gửi phản hồi bên dưới."
                }
            }

        // Gửi phản hồi lên admin
        btnSendFeedback.setOnClickListener {
            val message = etFeedback.text.toString().trim()
            if (message.isEmpty()) {
                etFeedback.error = "Vui lòng nhập nội dung"
                return@setOnClickListener
            }

            val user = auth.currentUser
            val feedback = hashMapOf(
                "uid" to uid,
                "email" to (user?.email ?: "unknown"),
                "subject" to "Yêu cầu mở khóa tài khoản",
                "message" to message,
                "created_at" to com.google.firebase.Timestamp.now().toDate().toString(),
                "type" to "unban_request"
            )

            db.collection("feedback").add(feedback)
                .addOnSuccessListener {
                    Toast.makeText(this, "Đã gửi phản hồi. Admin sẽ xem xét sớm.", Toast.LENGTH_LONG).show()
                    etFeedback.text.clear()
                }
                .addOnFailureListener { e ->
                    Toast.makeText(this, "Lỗi gửi: ${e.message}", Toast.LENGTH_SHORT).show()
                }
        }

        // Đăng xuất
        btnLogout.setOnClickListener {
            auth.signOut()
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
        }
    }

    // Chặn nút Back — không cho quay lại app chính
    override fun onBackPressed() {
        // Không làm gì — user phải đăng xuất hoặc gửi feedback
    }
}
