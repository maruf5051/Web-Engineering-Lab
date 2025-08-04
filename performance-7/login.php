<?php
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "coffee_shor");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error
$error = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['identifier']);
    $password = trim($_POST['password']);


    // Search by email or serial
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR serial = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($user['PASSWORD'] === $password) {
            $_SESSION['user_name'] = $user['NAME'];
            $_SESSION['user_id'] = $user['id'];
            header("Location: coffee.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found with that Email or Serial.";
    }
}
?>

<!-- Tailwind HTML Form -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <form method="POST" action="" class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-600">Login</h2>

        <!-- Error Message -->
        <?php if (!empty($error)) : ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Email or Serial -->
        <label class="block mb-2 text-sm font-medium text-gray-700">Email or Serial</label>
        <input type="text" name="identifier" placeholder="Email or Serial" required
            class="w-full px-4 py-2 mb-4 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">

        <!-- Password -->
        <label class="block mb-2 text-sm font-medium text-gray-700">Password</label>
        <input type="password" name="password" required
            class="w-full px-4 py-2 mb-6 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">

        <!-- Submit -->
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Login</button>

        <!-- Redirect to register -->
        <p class="mt-4 text-center text-sm text-gray-600">
            Don't have an account?
            <a href="register.php" class="text-blue-600 hover:underline">Register</a>
        </p>
    </form>
</body>

</html>