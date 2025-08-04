<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "coffee_shor");

// Error array
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $birthdate = trim($_POST['birthdate']);
    $password = trim($_POST['password']);

    // Email check
    if (!preg_match('/^[\w.-]+@cse\.diu\.edu\.bd$/', $email)) {
        $errors[] = "Email must end with @cse.diu.edu.bd";
    }

    // Password check
    if (!preg_match('/^[A-Za-z@_]+$/', $password)) {
        $errors[] = "Password can only contain letters, @, or _";
    }

    // Birthdate format check
    $dateObj = DateTime::createFromFormat("d-m-Y", $birthdate);
    if (!$dateObj) {
        $errors[] = "Birthdate format must be dd-mm-yyyy";
    }

    // Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "This email is already registered.";
    }

    // If no errors, insert into DB
    if (empty($errors)) {
        $birthFormatted = $dateObj->format("Y-m-d");
        $birthYear = $dateObj->format("Y");

        $result = $conn->query("SELECT COUNT(*) AS total FROM users");
        $row = $result->fetch_assoc();
        $count = $row['total'] + 1;
        $serial = $birthYear . '-' . str_pad($count, 3, "0", STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO users (email, name, birth, password, serial) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $username, $birthFormatted, $password, $serial);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Registration failed: " . $stmt->error;
        }
    }
}
?>

<!-- Tailwind HTML Form -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <form action="" method="POST" class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-600">Register</h2>

        <!-- Show Errors -->
        <?php if (!empty($errors)) : ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                <ul class="list-disc list-inside text-sm">
                    <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Email -->
        <label class="block mb-2 text-sm font-medium text-gray-700">Email (.cse@diu.edu.bd)</label>
        <input type="email" name="email" placeholder="example@cse.diu.edu.bd" required
            class="w-full px-4 py-2 mb-4 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400"
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

        <!-- Username -->
        <label class="block mb-2 text-sm font-medium text-gray-700">Username</label>
        <input type="text" name="username" required
            class="w-full px-4 py-2 mb-4 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400"
            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">

        <!-- Birthdate -->
        <label class="block mb-2 text-sm font-medium text-gray-700">Birthdate (dd-mm-yyyy)</label>
        <input type="text" name="birthdate" placeholder="dd-mm-yyyy" required
            class="w-full px-4 py-2 mb-4 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400"
            value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>">

        <!-- Password -->
        <label class="block mb-2 text-sm font-medium text-gray-700">Password (A-Za-z@_)</label>
        <input type="password" name="password" required
            class="w-full px-4 py-2 mb-6 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400">

        <!-- Submit -->
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Register</button>

        <!-- Redirect to login -->
        <p class="mt-4 text-center text-sm text-gray-600">
            Already have an account?
            <a href="login.php" class="text-blue-600 hover:underline">Login</a>
        </p>
    </form>

</body>

</html>