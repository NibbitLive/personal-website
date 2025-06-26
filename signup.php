<?php
session_start();

$error = isset($_GET['error']);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - BineChat</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <!-- Vanta.js & Three.js for background -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.birds.min.js"></script>

    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        #vanta-bg {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: -1;
        }

        .container {
            position: relative;
            z-index: 1;
            background-color: rgba(255, 255, 255, 0.85);
            padding: 30px;
            border-radius: 12px;
            backdrop-filter: blur(6px);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            margin-top: 60px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
        }

        .password-toggle-cross {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
            display: none;
        }

        .position-relative {
            position: relative;
        }
        
        /* Shake animation for error box */
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            50% { transform: translateX(10px); }
            75% { transform: translateX(-10px); }
            100% { transform: translateX(0); }
        }

        /* Custom error box styles */
        .alert-custom {
            background-color:rgb(255, 136, 0); /* Orange */
            color: white;
            animation: shake 0.5s ease-in-out; /* Apply shake animation */
        }
    </style>
</head>
<body>

    <!-- Animated Background -->
    <div id="vanta-bg"></div>

    <header class="text-center p-4 text-white">
        <h1>BineChat - Sign Up</h1>
    </header>

    <div class="container col-md-6">
        <?php if ($error): ?>
            <div id="error-message" class="alert alert-custom mb-4">
                🚫 Oops! Username already taken. Please try again.
            </div>
        <?php endif; ?>

        <form action="signup_process.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>

            <div class="form-group position-relative">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <img src="https://cdn-icons-png.flaticon.com/512/159/159604.png" width="20" height="20" alt="Eye" class="password-toggle" onclick="togglePassword()">
                <img src="https://cdn-icons-png.flaticon.com/512/159/159603.png" width="20" height="20" alt="Eye with Cross" class="password-toggle-cross" onclick="togglePassword()">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
        </form>

        <p class="mt-3 text-center">Already have an account? <a href="login.php">Log in here</a></p>
    </div>

    <script>
        // Initialize Vanta.js Birds effect
        VANTA.BIRDS({
            el: "#vanta-bg",
            mouseControls: true,
            touchControls: true,
            gyroControls: false,
            minHeight: 200.00,
            minWidth: 200.00,
            scale: 1.00,
            scaleMobile: 1.00,
            color1: 0xffffff,
            birdSize: 0.30,
            wingSpan: 13.00,
            speedLimit: 2.00,
            separation: 49.00,
            alignment: 29.00,
            cohesion: 99.00
        });

        // Toggle password visibility and eye icons
        function togglePassword() {
            const pwd = document.getElementById('password');
            const eye = document.querySelector('.password-toggle');
            const eyeCross = document.querySelector('.password-toggle-cross');

            if (pwd.type === 'password') {
                pwd.type = 'text';
                eye.style.display = 'none';
                eyeCross.style.display = 'block';
            } else {
                pwd.type = 'password';
                eye.style.display = 'block';
                eyeCross.style.display = 'none';
            }
        }
        
        // Hide error on input
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const errorMessage = document.getElementById('error-message');

        [usernameInput, passwordInput].forEach(input => {
            input.addEventListener('input', () => {
                if (errorMessage) errorMessage.style.display = 'none';
            });
        });
    </script>

</body>
</html>
