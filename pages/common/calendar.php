<?php
require_once '../../includes/config.php';
requireLogin();

$user = getCurrentUser();
$appointments = readJSON(APPOINTMENTS_FILE);
$users = readJSON(USERS_FILE);

// Get all doctors
$doctors = array_filter($users, function($u) {
    return $u['role'] === 'doctor';
});

// Filter appointments based on user role
if (isDoctor()) {
    $filteredAppointments = array_filter($appointments, function($apt) use ($user) {
        return $apt['doctor_id'] === $user['id'];
    });
} else {
    $filteredAppointments = array_filter($appointments, function($apt) use ($user) {
        return $apt['patient_id'] === $user['id'];
    });
}

// Convert appointments to calendar format
$calendarEvents = [];
foreach ($filteredAppointments as $apt) {
    $doctorName = '';
    $patientName = '';
    
    foreach ($users as $u) {
        if ($u['id'] === $apt['doctor_id']) {
            $doctorName = 'Dr. ' . $u['name'];
        }
        if ($u['id'] === $apt['patient_id']) {
            $patientName = $u['name'];
        }
    }
    
    $calendarEvents[] = [
        'id' => $apt['id'],
        'title' => isDoctor() ? $patientName : $doctorName,
        'start' => $apt['date'],
        'description' => $apt['reason'] ?? 'Check-up'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Clinic Management</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1>📆 Appointment Calendar</h1>
                <div class="user-info">
                    <?php if (isPatient()): ?>
                        <button onclick="openBookingModal()" class="btn btn-primary">Book Appointment</button>
                    <?php endif; ?>
                    <button class="btn btn-logout"><a href="/logout.php" class="btn-sm">Logout</a></button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Booking Modal -->
    <?php if (isPatient()): ?>
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Book Appointment</h2>
                <span class="close" onclick="closeBookingModal()">&times;</span>
            </div>
            <form action="/pages/common/appointments.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label for="doctor">Select Doctor</label>
                    <select id="doctor" name="doctor_id" required>
                        <option value="">Choose a doctor...</option>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?php echo $doc['id']; ?>">
                                Dr. <?php echo htmlspecialchars($doc['name']); ?>
                                <?php if (isset($doc['specialty'])): ?>
                                    - <?php echo htmlspecialchars($doc['specialty']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="time">Time</label>
                    <input type="time" id="time" name="time" required>
                </div>
                <div class="form-group">
                    <label for="reason">Reason for Visit</label>
                    <textarea id="reason" name="reason" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeBookingModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Book Appointment</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        const events = <?php echo json_encode($calendarEvents); ?>;
        
        function openBookingModal() {
            document.getElementById('bookingModal').style.display = 'block';
        }
        
        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('bookingModal');
            if (event.target == modal) {
                closeBookingModal();
            }
        }
    </script>
    <script src="/assets/js/calendar.js"></script>
</body>
</html>
