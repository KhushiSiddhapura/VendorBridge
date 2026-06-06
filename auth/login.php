<?php

include '../config/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($login) || empty($password)) {

        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'Please fill all fields'
        ];

        header('Location: ../login/login.php');
        exit();
    }

    // Search by username OR email
    $sql = "SELECT * FROM users
            WHERE username = '$login'
            OR email = '$login'
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Login successful'
            ];

            header('Location: ../dashboard/dashboard.html');
            exit();
        }
    }

    $_SESSION['toast'] = [
        'type' => 'fail',
        'message' => 'Invalid credentials'
    ];

    header('Location: ../login/login.php');
    exit();

} else {

    die('Invalid request method.');
}