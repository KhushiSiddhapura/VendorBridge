<?php
include '../config/connection.php';
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'Invalid data'
        ];

        header('Location: ../index.php');
        exit();
    }

    // Check if username already exists
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    
    if (mysqli_num_rows($result) === 1 && password_verify($password, $user['password'])) {

        $_SESSION['username'] = $user['username'];
        $_SESSION['id'] = $user['id'];

        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => 'login successfull'
        ];

        header('Location: ../dashbaord/dashbaord.html');
        exit();

    } else {
        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'invalid credentials'
        ];

        header("Location: ../index.php");
        exit();

    }
} else {
    die('Invalid request method.');
}
