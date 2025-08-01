<?php
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // البيانات القادمة من الفورم
    $image = $_FILES['photo']['tmp_name'];
    $description = $_POST['description'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $category = $_POST['category'];
    
    // حفظ الصورة
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $image_name = uniqid() . ".jpg";
    move_uploaded_file($image, $target_dir . $image_name);
    
    // الاتصال بقاعدة البيانات
    $conn = new mysqli("localhost", "root", "", "street_db");
    if ($conn->connect_error) {
        die("فشل الاتصال: " . $conn->connect_error);
    }
    
    // إدخال البيانات في الجدول (with category)
    $stmt = $conn->prepare("INSERT INTO reports (description, latitude, longitude, image_path, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sddss", $description, $latitude, $longitude, $image_name, $category);
    
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "تم إرسال البلاغ بنجاح"];
    } else {
        $response = ["status" => "error", "message" => "حدث خطأ أثناء إرسال البلاغ"];
    }
    
    $stmt->close();
    $conn->close();
    
    // If it's an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أبلغ عن مشكلة في مدينتك</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            direction: rtl;
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .auth-notice {
            background: #d1ecf1;
            color: #0c5460;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
            border: 1px solid #bee5eb;
        }
        
        .problem-type {
            margin-bottom: 2rem;
        }
        
        .problem-type h3 {
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .category-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .category-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .category-card input[type="radio"] {
            display: none;
        }
        
        .category-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .category-card.selected i {
            color: white;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .file-upload {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .file-upload.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .file-upload input[type="file"] {
            display: none;
        }
        
        .file-upload i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .file-upload p {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .preview-container {
            margin-top: 1rem;
            text-align: center;
        }
        
        .preview-image {
            max-width: 300px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .location-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .get-location-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .get-location-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 1.5rem;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin-top: 1rem;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            display: none;
            margin-top: 1rem;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            display: none;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .location-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-card">
            <div class="form-header">
                <h1>أبلغ عن مشكلة في مدينتك</h1>
                <p>ساعدنا في تحسين البنية الحضرية من خلال الإبلاغ عن المشاكل التي تواجهها</p>
            </div>

            <div class="auth-notice">
                <i class="fas fa-user-check"></i>
                مرحباً <?php echo htmlspecialchars($_SESSION['username'] ?? 'المستخدم'); ?>، أنت مسجل الدخول ويمكنك الآن إرسال البلاغات
            </div>

            <form id="reportForm" method="POST" enctype="multipart/form-data">
                <div class="problem-type">
                    <h3><i class="fas fa-exclamation-triangle"></i> نوع المشكلة</h3>
                    <div class="category-grid">
                        <label class="category-card">
                            <input type="radio" name="category" value="waste" required>
                            <i class="fas fa-trash" style="color: #28a745;"></i>
                            <span>مخلفات</span>
                        </label>
                        <label class="category-card">
                            <input type="radio" name="category" value="water_leak" required>
                            <i class="fas fa-tint" style="color: #007bff;"></i>
                            <span>تسريب مياه</span>
                        </label>
                        <label class="category-card">
                            <input type="radio" name="category" value="road_hole" required>
                            <i class="fas fa-road" style="color: #ffc107;"></i>
                            <span>حفر في الطريق</span>
                        </label>
                        <label class="category-card">
                            <input type="radio" name="category" value="lighting" required>
                            <i class="fas fa-lightbulb" style="color: #fd7e14;"></i>
                            <span>إنارة عامة</span>
                        </label>
                        <label class="category-card">
                            <input type="radio" name="category" value="buildings" required>
                            <i class="fas fa-building" style="color: #6c757d;"></i>
                            <span>مباني عامة</span>
                        </label>
                        <label class="category-card">
                            <input type="radio" name="category" value="trees" required>
                            <i class="fas fa-tree" style="color: #198754;"></i>
                            <span>الأشجار والحدائق</span>
                        </label>
                        <label class="category-card">
                            <input type="radio" name="category" value="parking" required>
                            <i class="fas fa-car" style="color: #e83e8c;"></i>
                            <span>مواقف السيارات</span>
                        </label>
                        <label class="category-card">
                            <input type="radio" name="category" value="other" required>
                            <i class="fas fa-ellipsis-h" style="color: #6f42c1;"></i>
                            <span>أخرى</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">وصف المشكلة *</label>
                    <textarea id="description" name="description" rows="4" placeholder="اكتب وصفاً مفصلاً عن المشكلة..." required></textarea>
                </div>

                <div class="form-group">
                    <label>إضافة صور *</label>
                    <div class="file-upload" id="fileUpload">
                        <input type="file" id="photo" name="photo" accept="image/*" required>
                        <i class="fas fa-camera"></i>
                        <p>انقر لإضافة صور أو اسحب الصور هنا</p>
                        <small>الحد الأقصى: 10 ميجابايت</small>
                    </div>
                    <div class="preview-container" id="previewContainer"></div>
                </div>

                <div class="location-group">
                    <div class="form-group">
                        <label for="latitude">خط العرض *</label>
                        <input type="number" id="latitude" name="latitude" step="any" placeholder="36.7538" required>
                    </div>
                    <div class="form-group">
                        <label for="longitude">خط الطول *</label>
                        <input type="number" id="longitude" name="longitude" step="any" placeholder="3.0588" required>
                    </div>
                    <button type="button" class="get-location-btn" id="getLocationBtn">
                        <i class="fas fa-map-marker-alt"></i>
                        تحديد الموقع الحالي
                    </button>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    إرسال البلاغ
                </button>

                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>جاري إرسال البلاغ...</p>
                </div>

                <div class="success-message" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    تم إرسال البلاغ بنجاح! شكراً لمساهمتك في تحسين المدينة.
                </div>

                <div class="error-message" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    حدث خطأ أثناء إرسال البلاغ. يرجى المحاولة مرة أخرى.
                </div>
            </form>
        </div>
    </div>

    <script>
        // Category selection
        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.category-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // File upload handling
        const fileUpload = document.getElementById('fileUpload');
        const photoInput = document.getElementById('photo');
        const previewContainer = document.getElementById('previewContainer');

        fileUpload.addEventListener('click', () => photoInput.click());

        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });

        fileUpload.addEventListener('dragleave', () => {
            fileUpload.classList.remove('dragover');
        });

        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                photoInput.files = files;
                previewImage(files[0]);
            }
        });

        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                previewImage(this.files[0]);
            }
        });

        function previewImage(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" class="preview-image">`;
            };
            reader.readAsDataURL(file);
        }

        // Get current location
        document.getElementById('getLocationBtn').addEventListener('click', function() {
            if (navigator.geolocation) {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري تحديد الموقع...';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
                        document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
                        this.innerHTML = '<i class="fas fa-check"></i> تم تحديد الموقع';
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-map-marker-alt"></i> تحديد الموقع الحالي';
                        }, 2000);
                    },
                    (error) => {
                        alert('لا يمكن تحديد الموقع الحالي. يرجى إدخال الإحداثيات يدوياً.');
                        this.innerHTML = '<i class="fas fa-map-marker-alt"></i> تحديد الموقع الحالی';
                    }
                );
            } else {
                alert('المتصفح لا يدعم تحديد الموقع الجغرافي.');
            }
        });

        // Form submission with AJAX
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            
            // Hide previous messages
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            
            // Show loading
            submitBtn.disabled = true;
            loading.style.display = 'block';
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.status === 'success') {
                    successMessage.style.display = 'block';
                    this.reset();
                    previewContainer.innerHTML = '';
                    document.querySelectorAll('.category-card').forEach(c => c.classList.remove('selected'));
                } else {
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                errorMessage.style.display = 'block';
                console.error('Error:', error);
            })
            .finally(() => {
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>