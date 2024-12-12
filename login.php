<?php
    // Start the session to store user data
    session_start();

    // Variable to hold error messages, initially empty
    $error = '';

    // Check if the form was submitted using POST method
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the user ID and password from the submitted form
        $inputId = $_POST['id'];
        $inputPassword = $_POST['password'];

        // Read each line from the users.csv file
        foreach (file('assets/data/users.csv') as $line) {
            // Split the line into individual values: ID, Name, Role, and Password
            list($id, $name, $role, $password) = str_getcsv($line);

            // Check if the input ID and password match the current line's ID and password
            if ($id === $inputId && $password === $inputPassword) {
                // Store user details in the session for later use
                $_SESSION['id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;

                // Redirect based on the user's role
                if ($role === 'Student') {
                    header('Location: student_dashboard.php');
                    exit; // Stop further script execution
                } elseif ($role === 'Admin') {
                    header('Location: admin_dashboard.php');
                    exit; // Stop further script execution
                }
            }
        }

        // If no match was found, set an error message
        $error = 'Invalid ID or password.';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Monitoring System - Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

    <div class="main-container">
        <div class="title-container">
            <h1>Attendance Monitoring System</h1>
        </div>

        <div class="container">
            <h2>Login</h2>

            <?php if ($error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="id">ID:</label>
                <input type="text" id="id" name="id" required><br><br>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br><br>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>

</body>
</html>