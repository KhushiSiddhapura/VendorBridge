<?php
require_once __DIR__ . '/connection.php';

echo "Seeding test users...\n";

// Helper to seed a user
function seedUser($conn, $first, $last, $user, $email, $pass, $role) {
    $hashed = password_hash($pass, PASSWORD_BCRYPT);
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$user'");
    if (mysqli_num_rows($check) > 0) {
        // Update password
        mysqli_query($conn, "UPDATE users SET password = '$hashed', role = '$role' WHERE username = '$user'");
        echo "✓ Updated user: $user (Role: $role)\n";
    } else {
        $sql = "INSERT INTO users (firstname, lastname, username, email, password, phone, role, country, description) 
                VALUES ('$first', '$last', '$user', '$email', '$hashed', 9876543210, '$role', 'India', 'Test Account for $role')";
        if (mysqli_query($conn, $sql)) {
            echo "✓ Created user: $user (Role: $role)\n";
        } else {
            echo "✗ Error creating user $user: " . mysqli_error($conn) . "\n";
        }
    }
}

seedUser($conn, 'Officer', 'One', 'officer1', 'officer1@example.com', 'officer1', 'procurement_officer');
seedUser($conn, 'Manager', 'One', 'manager1', 'manager1@example.com', 'manager1', 'manager');
seedUser($conn, 'Vendor', 'One', 'vendor1', 'vendor1@example.com', 'vendor1', 'vendor');

echo "Seeding completed successfully!\n";
?>
