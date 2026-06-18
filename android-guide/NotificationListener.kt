package com.nexus.app

import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.os.Build
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.Query

/**
 * Lắng nghe thông báo hệ thống từ admin (Firestore collection "notifications").
 *
 * CÁCH DỤNG:
 * Gọi NotificationListener.startListening(this) trong MainActivity.onCreate().
 *
 * Flow:
 * 1. Lắng nghe realtime collection "notifications" trên Firestore
 * 2. Khi có document mới → hiển thị notification trên điện thoại
 * 3. Chỉ thông báo cho document có created_at MỚI HƠN lần cuối đã xem
 */
object NotificationListener {

    private const val TAG = "NotifListener"
    private const val CHANNEL_ID = "nexus_system_notifications"
    private const val PREF_NAME = "nexus_prefs"
    private const val KEY_LAST_NOTIF_TIME = "last_notif_time"

    private var isListening = false

    fun startListening(context: Context) {
        if (isListening) return
        isListening = true

        createNotificationChannel(context)

        val db = FirebaseFirestore.getInstance()
        val prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
        val lastTime = prefs.getString(KEY_LAST_NOTIF_TIME, "") ?: ""

        // Lắng nghe realtime — snapshot listener
        db.collection("notifications")
            .orderBy("created_at", Query.Direction.DESCENDING)
            .limit(20)
            .addSnapshotListener { snapshots, error ->
                if (error != null) {
                    Log.e(TAG, "Lỗi lắng nghe thông báo: ${error.message}")
                    return@addSnapshotListener
                }

                if (snapshots == null || snapshots.isEmpty) return@addSnapshotListener

                for (change in snapshots.documentChanges) {
                    if (change.type == com.google.firebase.firestore.DocumentChange.Type.ADDED) {
                        val doc = change.document
                        val createdAt = doc.getString("created_at") ?: ""

                        // Chỉ thông báo nếu MỚI HƠN lần cuối đã xem
                        if (createdAt > lastTime) {
                            val title = doc.getString("title") ?: "Thông báo"
                            val body = doc.getString("body") ?: ""

                            showNotification(context, title, body)

                            // Cập nhật thời gian lần cuối
                            prefs.edit().putString(KEY_LAST_NOTIF_TIME, createdAt).apply()
                        }
                    }
                }
            }

        Log.d(TAG, "Đã bắt đầu lắng nghe thông báo hệ thống")
    }

    private fun showNotification(context: Context, title: String, body: String) {
        val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_notification) // Thay bằng icon của bạn
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .build()

        manager.notify(System.currentTimeMillis().toInt(), notification)
    }

    private fun createNotificationChannel(context: Context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Thông báo hệ thống",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Thông báo từ quản trị viên Nexus"
            }

            val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
            manager.createNotificationChannel(channel)
        }
    }
}
