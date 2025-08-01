<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['user_id']);
$userName = $isAuthenticated ? $_SESSION['username'] : 'غير مسجل';
$userRole = $isAuthenticated ? $_SESSION['role'] : '';
?>

<header class="header">
    <nav>
        <ul class="nav-links">
            <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">أبلغ عن مشكلة</a></li>
            <?php if ($isAuthenticated && $userRole === 'admin'): ?>
            <li><a href="admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">البلاغات</a></li>
            <?php endif; ?>
            <?php if (!$isAuthenticated): ?>
            <li><a href="login.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">تسجيل الدخول</a></li>
            <?php else: ?>
            <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"><?php echo htmlspecialchars($userName); ?></a></li>
            <li><a href="logout.php">تسجيل الخروج</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="user-info">
        <span>بلغني</span>
        <i class="fas fa-shield-alt"></i>
    </div>
</header>

<style>
.header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.nav-links {
    display: flex;
    gap: 2rem;
    list-style: none;
}

.nav-links a {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.nav-links a.active {
    background: rgba(255, 255, 255, 0.2);
}

.nav-links a:hover {
    background: rgba(255, 255, 255, 0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
}

@media (max-width: 768px) {
    .header {
        padding: 1rem;
        flex-direction: column;
        gap: 1rem;
    }
    
    .nav-links {
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>