<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoupleTodo</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>

    <nav>
        <div class="logo">
            ❤️ CoupleTodo
        </div>

        <a href="./login/login.html" class="login-btn">Login</a>
    </nav>

    <section class="hero">

        <div class="hero-content">
            <span class="tag">Made for Couples</span>

            <h1>
                Stay Connected,<br>
                Even Through Tasks
            </h1>

            <p>
                Share tasks, track each other's schedules,
                know when your partner is free, and achieve
                goals together.
            </p>

            <div class="buttons">
                <a href="./login/login.html" class="primary-btn">Get Started</a>
                <a href="#" class="secondary-btn">Learn More</a>
            </div>
        </div>

        <div class="hero-card">

            <div class="task-card">
                <h3>Nidhish's Tasks</h3>

                <div class="task completed">
                    ✓ Finish Project
                </div>

                <div class="task">
                    Study DSA
                </div>
            </div>

            <div class="task-card partner">
                <h3>khushu's Tasks</h3>

                <div class="task">
                    Gym Session
                </div>

                <div class="task completed">
                    ✓ Leetcode
                </div>

                <p class="status">
                    🟢 Free after 7 PM
                </p>
            </div>

        </div>

    </section>


</body>

</html>