<?php
session_start();

// Data file paths
define('USERS_FILE', __DIR__ . '/../data/users.json');
define('APPOINTMENTS_FILE', __DIR__ . '/../data/appointments.json');
define('PATIENTS_FILE', __DIR__ . '/../data/patients.json');

// Helper function to read JSON file
function readJSON($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

// Helper function to write JSON file
function writeJSON($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is doctor
function isDoctor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'doctor';
}

// Check if user is patient
function isPatient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /index.php');
        exit;
    }
}

// Require doctor role
function requireDoctor() {
    requireLogin();
    if (!isDoctor()) {
        header('Location: /pages/patient/dashboard.php');
        exit;
    }
}

// Require patient role
function requirePatient() {
    requireLogin();
    if (!isPatient()) {
        header('Location: /pages/doctor/dashboard.php');
        exit;
    }
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    $users = readJSON(USERS_FILE);
    foreach ($users as $user) {
        if ($user['id'] == $_SESSION['user_id']) {
            return $user;
        }
    }
    return null;
}
?>
