<?php
require_once '../../includes/config.php';
requirePatient();

$user = getCurrentUser();
$appointments = readJSON(APPOINTMENTS_FILE);
$patients = readJSON(PATIENTS_FILE);
$users = readJSON(USERS_FILE);

// Get user's appointments
$userAppointments = array_filter($appointments, function($apt) use ($user) {
    return $apt['patient_id'] === $user['id'];
});

// Sort by date
usort($userAppointments, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// Get upcoming appointments
$today = date('Y-m-d H:i:s');
$upcomingAppointments = array_filter($userAppointments, function($apt) use ($today) {
    return $apt['date'] >= $today;
});

// Get patient record
$patientRecord = null;
foreach ($patients as $p) {
    if ($p['patient_id'] === $user['id']) {
        $patientRecord = $p;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Clinic Management</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
                <div class="user-info">
                    <span>Patient Portal</span>
                    <button class="btn btn-logout"><a href="/logout.php" class="btn-sm">Logout</a></button>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3><?php echo count($upcomingAppointments); ?></h3>
                        <p>Upcoming Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <h3><?php echo count($userAppointments); ?></h3>
                        <p>Total Visits</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💊</div>
                    <div class="stat-info">
                        <h3><?php echo $patientRecord ? 'Active' : 'N/A'; ?></h3>
                        <p>Medical Record</p>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>My Appointments</h2>
                        <button  class="btn btn-primary"><a href="/pages/common/calendar.php" class="btn btn-sm">Book Appointment</a></button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userAppointments)): ?>
                            <p class="empty-state">No appointments scheduled</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Doctor</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($userAppointments, 0, 5) as $apt): ?>
                                        <?php
                                        $doctorName = 'Unknown';
                                        foreach ($users as $u) {
                                            if ($u['id'] === $apt['doctor_id']) {
                                                $doctorName = 'Dr. ' . $u['name'];
                                                break;
                                            }
                                        }
                                        $isPast = $apt['date'] < $today;
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($apt['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($doctorName); ?></td>
                                            <td><?php echo htmlspecialchars($apt['reason'] ?? 'Check-up'); ?></td>
                                            <td>
                                                <?php
                                            $status = $apt['status'] ?? 'pending';

                                            $badgeClass = match ($status) {
                                                'accepted' => 'badge-success',
                                                'pending'  => 'badge-primary',
                                                default    => 'badge-secondary'
                                            };
                                            ?>

                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
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
