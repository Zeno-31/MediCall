<?php
$currentUser = getCurrentUser();
$isDoctor = isDoctor();
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>🏥 Medi<img src = "/assets/img/red_telephone.svg" class = "img_telephone";>all</h2>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($isDoctor): ?>
            <a href="/pages/doctor/dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="/pages/common/calendar.php" class="nav-item">
                <span class="nav-icon">📆</span>
                <span>Calendar</span>
            </a>
            <a href="/pages/common/appointments.php" class="nav-item">
                <span class="nav-icon">📅</span>
                <span>Appointments</span>
            </a>
            <a href="/pages/common/patients.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span>Patients</span>
            </a>
        <?php else: ?>
            <a href="/pages/patient/dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="/pages/common/calendar.php" class="nav-item">
                <span class="nav-icon">📆</span>
                <span>Calendar</span>
            </a>
            <a href="/pages/common/appointments.php" class="nav-item">
                <span class="nav-icon">📅</span>
                <span>My Appointments</span>
            </a>
            <a href="/pages/common/patients.php" class="nav-item">
                <span class="nav-icon">💊</span>
                <span>My Record</span>
            </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                <div class="user-role"><?php echo ucfirst($currentUser['role']); ?></div>
            </div>
        </div>
    </div>
</div>
