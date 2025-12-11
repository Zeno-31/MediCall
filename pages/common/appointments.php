<?php
require_once '../../includes/config.php';
requireLogin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $doctor_id = $_POST['doctor_id'] ?? '';
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        
        if (empty($doctor_id) || empty($date) || empty($time) || empty($reason)) {
            $error = 'Please fill in all fields';
        } else {
            $appointments = readJSON(APPOINTMENTS_FILE);
            
            $newAppointment = [
                'id' => uniqid(),
                'patient_id' => $user['id'],
                'doctor_id' => $doctor_id,
                'date' => $date . ' ' . $time . ':00',
                'reason' => $reason,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $appointments[] = $newAppointment;
            writeJSON(APPOINTMENTS_FILE, $appointments);
            
            $message = 'Appointment booked successfully!';
        }
    } elseif ($action === 'update') {
        $apt_id = $_POST['appointment_id'] ?? '';
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        
        if (empty($apt_id) || empty($date) || empty($time)) {
            $error = 'Invalid appointment data';
        } else {
            $appointments = readJSON(APPOINTMENTS_FILE);
            
            foreach ($appointments as &$apt) {
                if ($apt['id'] === $apt_id) {
                    // Check permission
                    if (isDoctor() && $apt['doctor_id'] !== $user['id']) {
                        $error = 'Unauthorized';
                        break;
                    }
                    if (isPatient() && $apt['patient_id'] !== $user['id']) {
                        $error = 'Unauthorized';
                        break;
                    }
                    
                    $apt['date'] = $date . ' ' . $time . ':00';
                    $apt['reason'] = $reason;
                    $message = 'Appointment updated successfully!';
                    break;
                }
            }
            
            if (empty($error)) {
                writeJSON(APPOINTMENTS_FILE, $appointments);
            }
        }
    } elseif ($action === 'delete') {
        $apt_id = $_POST['appointment_id'] ?? '';
        
        if (empty($apt_id)) {
            $error = 'Invalid appointment ID';
        } else {
            $appointments = readJSON(APPOINTMENTS_FILE);
            $newAppointments = [];
            
            foreach ($appointments as $apt) {
                if ($apt['id'] === $apt_id) {
                    // Check permission
                    if (isDoctor() && $apt['doctor_id'] !== $user['id']) {
                        $error = 'Unauthorized';
                        break;
                    }
                    if (isPatient() && $apt['patient_id'] !== $user['id']) {
                        $error = 'Unauthorized';
                        break;
                    }
                    continue; // Skip this appointment (delete it)
                }
                $newAppointments[] = $apt;
            }
            
            if (empty($error)) {
                writeJSON(APPOINTMENTS_FILE, $newAppointments);
                $message = 'Appointment removed successfully!';
            }
        }
    } elseif ($action === 'accept') {
    $apt_id = $_POST['appointment_id'] ?? '';

    if (empty($apt_id)) {
        $error = 'Invalid appointment ID';
    } else {
        $appointments = readJSON(APPOINTMENTS_FILE);
        $newAppointments = [];

        foreach ($appointments as $apt) {
            if ($apt['id'] === $apt_id) {

                // Permission check
                if (isDoctor() && $apt['doctor_id'] !== $user['id']) {
                    $error = 'Unauthorized';
                    break;
                }

                // Update the appointment instead of deleting it
                $apt['status'] = 'accepted';
                $message = 'Appointment accepted successfully!';
            }

            // Keep the (possibly updated) appointment
            $newAppointments[] = $apt;
        }

        if (empty($error)) {
            writeJSON(APPOINTMENTS_FILE, $newAppointments);
        }
    }
}
}


// Get appointments
$appointments = readJSON(APPOINTMENTS_FILE);
$users = readJSON(USERS_FILE);

// Filter based on role
if (isDoctor()) {
    $filteredAppointments = array_filter($appointments, function($apt) use ($user) {
        return $apt['doctor_id'] === $user['id'];
    });
} else {
    $filteredAppointments = array_filter($appointments, function($apt) use ($user) {
        return $apt['patient_id'] === $user['id'];
    });
}

// Sort by date
usort($filteredAppointments, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// Get appointment to edit
$editAppointment = null;
if (isset($_GET['edit'])) {
    foreach ($appointments as $apt) {
        if ($apt['id'] === $_GET['edit']) {
            // Check ownership before allowing edit
            if (isDoctor() && $apt['doctor_id'] === $user['id']) {
                $editAppointment = $apt;
                break;
            } elseif (isPatient() && $apt['patient_id'] === $user['id']) {
                $editAppointment = $apt;
                break;
            }
        }
    }
}
?>

<?php
$currentUser = getCurrentUser();
$isDoctor = isDoctor();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Clinic Management</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

    <!-- DOCTOR APPOINTMENT -->
     <?php if ($isDoctor): ?>
    <div class="dashboard">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1>📅 Appointments</h1>
                <div class="user-info">
                    <?php if (isPatient()): ?>
                        <button class="btn btn-primary"><a href="/pages/common/calendar.php">Book New</a></button>
                    <?php endif; ?>
                    <button class="btn btn-logout"><a href="/logout.php" class="btn-sm">Logout</a></button>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>All Appointments</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($filteredAppointments)): ?>
                        <p class="empty-state">No appointments found</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <?php if (isDoctor()): ?>
                                        <th>Patient</th>
                                    <?php else: ?>
                                        <th>Doctor</th>
                                    <?php endif; ?>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredAppointments as $apt): ?>
                                    <?php
                                    $otherPersonName = 'Unknown';
                                    $searchId = isDoctor() ? $apt['patient_id'] : $apt['doctor_id'];
                                    foreach ($users as $u) {
                                        if ($u['id'] === $searchId) {
                                            $otherPersonName = isDoctor() ? $u['name'] : 'Dr. ' . $u['name'];
                                            break;
                                        }
                                    }
                                    $isPast = $apt['date'] < date('Y-m-d H:i:s');
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($apt['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($otherPersonName); ?></td>
                                        <td><?php echo htmlspecialchars($apt['reason']); ?></td>
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
                                        <td>
                                            <?php if ($apt['status'] !== 'accepted'): ?>
                                                <!-- EDIT BUTTON -->
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Accept this appointmet?');">
                                                <input type="hidden" name="action" value="accept">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Accept</button>
                                            </form>

                                                <!-- CANCEL BUTTON -->
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this appointment?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                </form>
                                            <?php else: ?>
                                                <!-- DISABLED STATE -->
                                                <button class="btn btn-sm" disabled>Accept</button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this appointment?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                </form>
                                            <?php endif; ?>
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

    <!-- PATIENT APPOINTMENT -->
     <?php else: ?>
        <div class="dashboard">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1>📅 Appointments</h1>
                <div class="user-info">
                    <?php if (isPatient()): ?>
                        <button class="btn btn-primary"><a href="/pages/common/calendar.php">Book New</a></button>
                    <?php endif; ?>
                    <button class="btn btn-logout"><a href="/logout.php" class="btn-sm">Logout</a></button>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>All Appointments</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($filteredAppointments)): ?>
                        <p class="empty-state">No appointments found</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <?php if (isDoctor()): ?>
                                        <th>Patient</th>
                                    <?php else: ?>
                                        <th>Doctor</th>
                                    <?php endif; ?>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredAppointments as $apt): ?>
                                    <?php
                                    $otherPersonName = 'Unknown';
                                    $searchId = isDoctor() ? $apt['patient_id'] : $apt['doctor_id'];
                                    foreach ($users as $u) {
                                        if ($u['id'] === $searchId) {
                                            $otherPersonName = isDoctor() ? $u['name'] : 'Dr. ' . $u['name'];
                                            break;
                                        }
                                    }
                                    $isPast = $apt['date'] < date('Y-m-d H:i:s');
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($apt['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($otherPersonName); ?></td>
                                        <td><?php echo htmlspecialchars($apt['reason']); ?></td>
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
                                        <td>
                                            <?php if ($apt['status'] !== 'accepted'): ?>
                                                <!-- EDIT BUTTON -->
                                                <a href="?edit=<?php echo $apt['id']; ?>" class="btn btn-sm">Edit</a>

                                                <!-- CANCEL BUTTON -->
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this appointment?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                </form>
                                            <?php else: ?>
                                                <!-- DISABLED STATE -->
                                                <button class="btn btn-sm" disabled>Edit</button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this appointment?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Edit Modal -->
    <?php if ($editAppointment): ?>
    <div id="editModal" class="modal" style="display: block">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Appointment</h2>
                <a href="/pages/common/appointments.php" class="close">&times;</a>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="appointment_id" value="<?php echo $editAppointment['id']; ?>">
                
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d', strtotime($editAppointment['date'])); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="time" value="<?php echo date('H:i', strtotime($editAppointment['date'])); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" rows="3" required><?php echo htmlspecialchars($editAppointment['reason']); ?></textarea>
                </div>
                
                <div class="modal-footer">
                    <a href="/pages/common/appointments.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?> 
    </div>





    <?php endif; ?>

    
    
</body>
</html>
