<?php
require_once '../../includes/config.php';
requireDoctor();

$user = getCurrentUser();
$appointments = readJSON(APPOINTMENTS_FILE);
$patients = readJSON(PATIENTS_FILE);
$users = readJSON(USERS_FILE);

// Get today's appointments
$today = date('Y-m-d');
$todayAppointments = array_filter($appointments, function($apt) use ($today, $user) {
    return $apt['doctor_id'] === $user['id'] && strpos($apt['date'], $today) === 0;
});

// Get upcoming appointments
$upcomingAppointments = array_filter($appointments, function($apt) use ($today, $user) {
    return $apt['doctor_id'] === $user['id'] && $apt['date'] >= $today;
});
usort($upcomingAppointments, function($a, $b) {
    return strcmp($a['date'], $b['date']);
});
$upcomingAppointments = array_slice($upcomingAppointments, 0, 5);

// Count total patients for this doctor
$doctorPatients = array_filter($patients, function($p) use ($user) {
    return isset($p['doctor_id']) && $p['doctor_id'] === $user['id'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Clinic Management</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1>Welcome, Dr. <?php echo htmlspecialchars($user['name']); ?></h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['specialty'] ?? 'General Practice'); ?></span>
                    <button class="btn btn-logout"><a href="/logout.php" class="btn-sm">Logout</a></button>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3><?php echo count($todayAppointments); ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo count($doctorPatients); ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <h3><?php echo count($upcomingAppointments); ?></h3>
                        <p>Upcoming Appointments</p>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Upcoming Appointments</h2>
                        <button class="btn btn-primary"><a href="/pages/common/calendar.php" class="btn btn-sm">View Calendar</a></button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingAppointments)): ?>
                            <p class="empty-state">No upcoming appointments</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <th>Reason</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingAppointments as $apt): ?>
                                        <?php
                                        $patientName = 'Unknown';
                                        foreach ($users as $u) {
                                            if ($u['id'] === $apt['patient_id']) {
                                                $patientName = $u['name'];
                                                break;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($apt['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($patientName); ?></td>
                                            <td><?php echo htmlspecialchars($apt['reason'] ?? 'Check-up'); ?></td>
                                            <td>
                                                <a href="/pages/common/appointments.php?edit=<?php echo $apt['id']; ?>" class="btn btn-sm">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
