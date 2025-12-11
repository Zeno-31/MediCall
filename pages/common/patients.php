<?php
require_once '../../includes/config.php';
requireLogin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isDoctor()) {
        $error = 'Only doctors can modify patient records';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $patient_user_id = $_POST['patient_user_id'] ?? '';
            $blood_type = trim($_POST['blood_type'] ?? '');
            $allergies = trim($_POST['allergies'] ?? '');
            $medical_history = trim($_POST['medical_history'] ?? '');
            
            if (empty($patient_user_id)) {
                $error = 'Please select a patient';
            } else {
                $patients = readJSON(PATIENTS_FILE);
                
                // Check if record already exists
                $exists = false;
                foreach ($patients as $p) {
                    if ($p['patient_id'] === $patient_user_id) {
                        $exists = true;
                        break;
                    }
                }
                
                if ($exists) {
                    $error = 'Patient record already exists';
                } else {
                    $newPatient = [
                        'id' => uniqid(),
                        'patient_id' => $patient_user_id,
                        'doctor_id' => $user['id'],
                        'blood_type' => $blood_type,
                        'allergies' => $allergies,
                        'medical_history' => $medical_history,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $patients[] = $newPatient;
                    writeJSON(PATIENTS_FILE, $patients);
                    
                    $message = 'Patient record created successfully!';
                }
            }
        } elseif ($action === 'update') {
            $record_id = $_POST['record_id'] ?? '';
            $blood_type = trim($_POST['blood_type'] ?? '');
            $allergies = trim($_POST['allergies'] ?? '');
            $medical_history = trim($_POST['medical_history'] ?? '');
            
            if (empty($record_id)) {
                $error = 'Invalid record ID';
            } else {
                $patients = readJSON(PATIENTS_FILE);
                
                foreach ($patients as &$p) {
                    if ($p['id'] === $record_id && $p['doctor_id'] === $user['id']) {
                        $p['blood_type'] = $blood_type;
                        $p['allergies'] = $allergies;
                        $p['medical_history'] = $medical_history;
                        $p['updated_at'] = date('Y-m-d H:i:s');
                        $message = 'Patient record updated successfully!';
                        break;
                    }
                }
                
                writeJSON(PATIENTS_FILE, $patients);
            }
        } elseif ($action === 'delete') {
            $record_id = $_POST['record_id'] ?? '';
            
            if (empty($record_id)) {
                $error = 'Invalid record ID';
            } else {
                $patients = readJSON(PATIENTS_FILE);
                $newPatients = [];
                
                foreach ($patients as $p) {
                    if ($p['id'] === $record_id && $p['doctor_id'] === $user['id']) {
                        continue; // Skip (delete)
                    }
                    $newPatients[] = $p;
                }
                
                writeJSON(PATIENTS_FILE, $newPatients);
                $message = 'Patient record deleted successfully!';
            }
        }
    }
}

// Get data
$patients = readJSON(PATIENTS_FILE);
$users = readJSON(USERS_FILE);

// Filter records based on role
if (isDoctor()) {
    $filteredPatients = array_filter($patients, function($p) use ($user) {
        return $p['doctor_id'] === $user['id'];
    });
    
    // Get all patient users for dropdown
    $patientUsers = array_filter($users, function($u) {
        return $u['role'] === 'patient';
    });
} else {
    $filteredPatients = array_filter($patients, function($p) use ($user) {
        return $p['patient_id'] === $user['id'];
    });
}

// Get record to edit
$editRecord = null;
if (isset($_GET['edit']) && isDoctor()) {
    foreach ($patients as $p) {
        if ($p['id'] === $_GET['edit'] && $p['doctor_id'] === $user['id']) {
            $editRecord = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isDoctor() ? 'Patient Records' : 'My Medical Record'; ?> - Clinic Management</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><?php echo isDoctor() ? '👥 Patient Records' : '💊 My Medical Record'; ?></h1>
                <div class="user-info">
                    <?php if (isDoctor()): ?>
                        <button onclick="openCreateModal()" class="btn btn-primary">Add Patient</button>
                    <?php endif; ?>
                    <button class="btn btn-logout"><a href="/logout.php" class=" btn-sm">Logout</a></button>
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
                    <h2><?php echo isDoctor() ? 'All Patient Records' : 'Medical Information'; ?></h2>
                </div>
                <div class="card-body">
                    <?php if (empty($filteredPatients)): ?>
                        <p class="empty-state">No patient records found</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <?php if (isDoctor()): ?>
                                        <th>Patient Name</th>
                                    <?php endif; ?>
                                    <th>Blood Type</th>
                                    <th>Allergies</th>
                                    <th>Medical History</th>
                                    <th>Last Updated</th>
                                    <?php if (isDoctor()): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredPatients as $record): ?>
                                    <?php
                                    $patientName = 'Unknown';
                                    foreach ($users as $u) {
                                        if ($u['id'] === $record['patient_id']) {
                                            $patientName = $u['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <?php if (isDoctor()): ?>
                                            <td><?php echo htmlspecialchars($patientName); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($record['blood_type'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($record['allergies'] ?: 'None'); ?></td>
                                        <td><?php echo htmlspecialchars($record['medical_history'] ?: 'None'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($record['updated_at'])); ?></td>
                                        <?php if (isDoctor()): ?>
                                            <td>
                                                <a href="?edit=<?php echo $record['id']; ?>" class="btn btn-sm">Edit</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this record?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Modal -->
    <?php if (isDoctor()): ?>
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Patient Record</h2>
                <span class="close" onclick="closeCreateModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Select Patient</label>
                    <select name="patient_user_id" required>
                        <option value="">Choose a patient...</option>
                        <?php foreach ($patientUsers as $pu): ?>
                            <option value="<?php echo $pu['id']; ?>"><?php echo htmlspecialchars($pu['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Blood Type</label>
                    <input type="text" name="blood_type" placeholder="e.g., A+, O-, B+">
                </div>
                
                <div class="form-group">
                    <label>Allergies</label>
                    <textarea name="allergies" rows="2" placeholder="List any known allergies"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Medical History</label>
                    <textarea name="medical_history" rows="4" placeholder="Previous conditions, surgeries, etc."></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeCreateModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Record</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }
        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }
    </script>
    <?php endif; ?>
    
    <!-- Edit Modal -->
    <?php if ($editRecord): ?>
    <div id="editModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Patient Record</h2>
                <a href="/pages/common/patients.php" class="close">&times;</a>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="record_id" value="<?php echo $editRecord['id']; ?>">
                
                <div class="form-group">
                    <label>Blood Type</label>
                    <input type="text" name="blood_type" value="<?php echo htmlspecialchars($editRecord['blood_type']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Allergies</label>
                    <textarea name="allergies" rows="2"><?php echo htmlspecialchars($editRecord['allergies']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Medical History</label>
                    <textarea name="medical_history" rows="4"><?php echo htmlspecialchars($editRecord['medical_history']); ?></textarea>
                </div>
                
                <div class="modal-footer">
                    <a href="/pages/common/patients.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
