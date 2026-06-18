<?php
// Gọi autoload của Composer để nạp thư viện
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/firestore_rest.php';

use Kreait\Firebase\Factory;

// Đường dẫn tới file JSON chứa khóa bảo mật
$serviceAccountKeyFile = __DIR__.'/firebase_credentials.json';

try {
    // Khởi tạo Firebase Factory
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountKeyFile);

    // Khởi tạo Auth (Dùng để khóa/mở khóa/xóa người dùng, quản lý tài khoản)
    $auth = $factory->createAuth();

    // Khởi tạo Firestore qua REST API (không cần ext-grpc)
    $firestore = new FirestoreRest($serviceAccountKeyFile);

} catch (Exception $e) {
    echo "Lỗi kết nối Firebase: " . $e->getMessage();
    exit();
}
?>