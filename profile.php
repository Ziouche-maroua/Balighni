<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "street_db");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Get current user data
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Validate username
    if ($username) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message = "اسم المستخدم مستخدم بالفعل";
            $message_type = 'error';
        }
    }

    // Validate password change
    if ($new_password && !$message) {
        if (!password_verify($current_password, $user['password'])) {
            $message = "كلمة المرور الحالية غير صحيحة";
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
            $message_type = 'error';
        }
    }

    // Update user data
    if (!$message && ($username || $new_password)) {
        $sql = "UPDATE users SET ";
        $params = [];
        $types = '';

        if ($username) {
            $sql .= "username = ?";
            $params[] = $username;
            $types .= 's';
        }
        
        if ($new_password) {
            $sql .= ($username ? ", " : "") . "password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            $types .= 's';
        }

        $sql .= " WHERE id = ?";
        $params[] = $user_id;
        $types .= 'i';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $message = "تم تحديث الملف الشخصي بنجاح";
            $message_type = 'success';
        } else {
            $message = "حدث خطأ أثناء التحديث";
            $message_type = 'error';
        }
    }
}

// Get user data
$stmt = $conn->prepare("SELECT username, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get report count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reports WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$report_count = $stmt->get_result()->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            direction: rtl;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 20px;
            color: white;
        }

        .content {
            padding: 30px;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-right: 4px solid #3498db;
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #7f8c8d;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .role-badge {
            padding: 5px 12px;
            border-radius: 15px;
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .role-admin {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .role-user {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }

        .edit-form {
            background: white;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            padding: 25px;
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <h1>الملف الشخصي</h1>
            <p>مرحباً <?= htmlspecialchars($user['username']) ?></p>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $report_count ?></div>
                    <div class="stat-label">عدد البلاغات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= date('Y', strtotime($user['created_at'])) ?></div>
                    <div class="stat-label">سنة الانضمام</div>
                </div>
            </div>

            <div class="profile-info">
                <div class="info-card">
                    <h3>📋 المعلومات الأساسية</h3>
                    <div class="info-item">
                        <span class="info-label">اسم المستخدم:</span>
                        <span class="info-value"><?= htmlspecialchars($user['username']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">معرف المستخدم:</span>
                        <span class="info-value">#<?= $user_id ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <h3>🔐 معلومات الحساب</h3>
                    <div class="info-item">
                        <span class="info-label">نوع الحساب:</span>
                        <span class="role-badge <?= $user['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                            <?= $user['role'] === 'admin' ? '👑 مدير' : '👤 مستخدم' ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">تاريخ التسجيل:</span>
                        <span class="info-value"><?= date('Y-m-d', strtotime($user['created_at'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">عدد البلاغات:</span>
                        <span class="info-value"><?= $report_count ?> بلاغ</span>
                    </div>
                </div>
            </div>

            <div class="edit-form">
                <h2 style="text-align: center; margin-bottom: 25px; color: #2c3e50;">✏️ تعديل الملف الشخصي</h2>
                
                <form method="POST">
                    <div class="form-section">
                        <h3>المعلومات الشخصية</h3>
                        <div class="form-group">
                            <label for="username">اسم المستخدم</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>تغيير كلمة المرور</h3>
                        <div class="form-group">
                            <label for="current_password">كلمة المرور الحالية</label>
                            <input type="password" id="current_password" name="current_password" placeholder="اتركه فارغاً إذا لم ترد تغيير كلمة المرور">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">كلمة المرور الجديدة</label>
                                <input type="password" id="new_password" name="new_password" placeholder="6 أحرف على الأقل">
                            </div>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-success">💾 حفظ التغييرات</button>
                        <a href="dashboard.php" class="btn btn-secondary">🏠 العودة للرئيسية</a>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="admin.php" class="btn btn-primary">🏛️ لوحة الإدارة</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const form = document.querySelector('form');
        form.addEventListener('submit', (e) => {
            const newPassword = document.getElementById('new_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            if (newPassword && !currentPassword) {
                alert('يرجى إدخال كلمة المرور الحالية');
                e.preventDefault();
            } else if (newPassword && newPassword.length < 6) {
                alert('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>