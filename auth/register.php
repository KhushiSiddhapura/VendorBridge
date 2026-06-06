<?php

include '../config/connection.php';
require_once '../services/mailService.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstname  = trim($_POST['firstname'] ?? '');
    $lastname   = trim($_POST['lastname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $role       = trim($_POST['role'] ?? '');
    $country    = trim($_POST['country'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (
        empty($firstname) ||
        empty($lastname) ||
        empty($email) ||
        empty($phone) ||
        empty($role) ||
        empty($country)
    ) {

        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'Please fill all required fields'
        ];

        header('Location: ../register/register.php');
        exit();
    }

    // Check if email already exists
    $checkEmail = mysqli_query(
        $conn,
        "SELECT id FROM users WHERE email = '$email'"
    );

    if (mysqli_num_rows($checkEmail) > 0) {

        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => 'Email already exists'
        ];

        header('Location: ../register/register.php');

        exit();
    }

    // Generate username
    $baseUsername = strtolower(
        preg_replace('/[^a-zA-Z0-9]/', '', $firstname) .
        '_' .
        preg_replace('/[^a-zA-Z0-9]/', '', $lastname)
    );

    // Ensure username is unique
    do {

        $randomNumber = rand(1, 9999);

        $username = $baseUsername . $randomNumber;

        // Default password = username
        $hashedPassword = password_hash($username, PASSWORD_BCRYPT);
        
        $checkUsername = mysqli_query(
            $conn,
            "SELECT id FROM users WHERE username = '$username'"
        );

    } while (mysqli_num_rows($checkUsername) > 0);

    // Insert user (status defaults to 'pending' – admin must approve before login)
    $sql = "INSERT INTO users (
            firstname,
            lastname,
            username,
            email,
            password,
            phone,
            role,
            country,
            description,
            status
        )
        VALUES (
            '$firstname',
            '$lastname',
            '$username',
            '$email',
            '$hashedPassword',
            '$phone',
            '$role',
            '$country',
            '$description',
            'pending'
        )";

    if (mysqli_query($conn, $sql)) {

        $_SESSION['toast'] = [
            'type'    => 'success',
            'message' => 'Account created successfully. Approve it in User Approvals before the user can log in.'
        ];

        sendCredentialsMail($email, $firstname, $username, $username);

        header('Location: ../register/register.php');
        exit();

    } else {

        $_SESSION['toast'] = [
            'type' => 'fail',
            'message' => mysqli_error($conn)
        ];

        header('Location: ../index.php');
        exit();
    }

} else {
    die('Invalid request method.');
}