<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("🚫 Access Denied. Admins only.");
}

$conn = new mysqli("localhost", "root", "", "street_db");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// Handle status update
if ($_POST['action'] ?? '' === 'update_status') {
    $id = intval($_POST['report_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$result = $conn->query("SELECT * FROM reports ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإدارة - البلاغات</title>
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
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
        }

        .table-container {
            padding: 30px;
            overflow-x: auto;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .reports-table th {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 15px;
            font-weight: 600;
            text-align: center;
        }

        .reports-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }

        .reports-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            color: white;
            display: inline-block;
            min-width: 100px;
        }

        .status-new { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .status-processing { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); }
        .status-done { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); }

        .report-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .report-image:hover {
            transform: scale(1.1);
        }

        .action-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .location-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }

        .location-link:hover {
            text-decoration: underline;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }

        .modal-body {
            padding: 30px;
        }

        .close {
            color: white;
            float: left;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            opacity: 0.7;
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

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-save {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }

        .btn-cancel:hover {
            background: #7f8c8d;
        }

        .image-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }

        .image-modal-content {
            display: block;
            margin: auto;
            max-width: 90%;
            max-height: 90%;
            margin-top: 2%;
        }

        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .reports-table {
                font-size: 14px;
            }
            
            .reports-table th, .reports-table td {
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <div class="header">
            <h1>🏛️ لوحة الإدارة</h1>
            <p>إدارة البلاغات والتقارير</p>
        </div>

        <?php
        // Calculate statistics
        $conn->query("SET NAMES utf8");
        $stats = [
            'total' => 0,
            'new' => 0,
            'processing' => 0,
            'done' => 0
        ];
        
        $result_stats = $conn->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
        while ($row = $result_stats->fetch_assoc()) {
            $stats['total'] += $row['count'];
            if ($row['status'] === 'جديد') $stats['new'] = $row['count'];
            elseif ($row['status'] === 'قيد المعالجة') $stats['processing'] = $row['count'];
            elseif ($row['status'] === 'تم الحل') $stats['done'] = $row['count'];
        }
        ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">إجمالي البلاغات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #e74c3c;"><?= $stats['new'] ?></div>
                <div class="stat-label">بلاغات جديدة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #f39c12;"><?= $stats['processing'] ?></div>
                <div class="stat-label">قيد المعالجة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #27ae60;"><?= $stats['done'] ?></div>
                <div class="stat-label">تم الحل</div>
            </div>
        </div>

        <div class="table-container">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الوصف</th>
                        <th>النوع</th>
                        <th>الموقع</th>
                        <th>الصورة</th>
                        <th>الحالة</th>
                        <th>تاريخ البلاغ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $result = $conn->query("SELECT * FROM reports ORDER BY created_at DESC");
                    while ($row = $result->fetch_assoc()): 
                        $statusClass = "status-new";
                        if ($row['status'] === 'قيد المعالجة') $statusClass = "status-processing";
                        if ($row['status'] === 'تم الحل') $statusClass = "status-done";
                    ?>
                        <tr>
                            <td><strong><?= $row['id'] ?></strong></td>
                            <td style="max-width: 200px; text-align: right;">
                                <?= htmlspecialchars(mb_substr($row['description'], 0, 50)) ?>
                                <?= mb_strlen($row['description']) > 50 ? '...' : '' ?>
                            </td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td>
                                <a href="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>" 
                                   target="_blank" class="location-link">
                                    📍 عرض الموقع
                                </a>
                            </td>
                            <td>
                                <img src="uploads/<?= $row['image_path'] ?>" 
                                     alt="صورة البلاغ" 
                                     class="report-image"
                                     onclick="showImageModal('uploads/<?= $row['image_path'] ?>')">
                            </td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <button class="action-btn" onclick="openEditModal(<?= $row['id'] ?>, '<?= addslashes($row['status']) ?>', '<?= addslashes($row['description']) ?>')">
                                    ✏️ تعديل
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Status Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h2>تعديل حالة البلاغ</h2>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="report_id" id="reportId">
                    
                    <div class="form-group">
                        <label>وصف البلاغ:</label>
                        <p id="reportDescription" style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-right: 4px solid #3498db;"></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">حالة البلاغ:</label>
                        <select name="status" id="status" required>
                            <option value="جديد">🔴 جديد</option>
                            <option value="قيد المعالجة">🟡 قيد المعالجة</option>
                            <option value="تم الحل">🟢 تم الحل</option>
                        </select>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">إلغاء</button>
                        <button type="submit" class="btn-save">💾 حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <img class="image-modal-content" id="modalImage">
    </div>

    <script>
        function openEditModal(id, status, description) {
            document.getElementById('reportId').value = id;
            document.getElementById('status').value = status;
            document.getElementById('reportDescription').textContent = description;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function showImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'block';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const imageModal = document.getElementById('imageModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === imageModal) {
                closeImageModal();
            }
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
                closeImageModal();
            }
        });
    </script>
</body>
</html>