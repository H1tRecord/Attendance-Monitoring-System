<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['id']) || !isset($_SESSION['name'])) {
        header('Location: login.php'); // Redirect to login page if not logged in
        exit;
    }

    // Function to read users.csv and return an array of students
    function getStudentsFromCSV($file) {
        $students = [];
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Read each row and filter out students
            while (($data = fgetcsv($handle)) !== FALSE) {
                list($id, $name, $role, $password) = $data;
                if ($role === 'Student') {
                    $students[] = ['ID' => $id, 'Name' => $name];
                }
            }
            fclose($handle);
        }
        return $students;
    }

    // Handle sign out logic
    if (isset($_GET['logout'])) {
        // Destroy the session and redirect to the login page
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Load students from the CSV
    $students = getStudentsFromCSV('assets/data/users.csv');

    // Function to generate a random attendance ID
    function generateAttendanceID() {
        return strtoupper(bin2hex(random_bytes(4))); // Random 8-character hex string
    }

    // Function to update the attendance status in a student's file
    function updateAttendanceStatus($id, $status, $date, $studentName) {
        // Generate a random Attendance ID
        $attendanceID = generateAttendanceID();

        // Student's individual attendance file (e.g., 1.csv for student with ID 1)
        $studentFile = "assets/data/student_data/{$id}.csv";
        $attendanceRecord = [$attendanceID, $id, $studentName, $date, $status];  // Include status
        
        // Create or append to the student's individual file
        if (($handle = fopen($studentFile, 'a')) !== FALSE) {
            fputcsv($handle, $attendanceRecord);
            fclose($handle);
        }
    }

    // Handle attendance update when an action link is clicked
    if (isset($_GET['id']) && isset($_GET['status']) && isset($_GET['date'])) {
        $id = $_GET['id'];
        $status = $_GET['status'];
        $date = $_GET['date']; // Date passed from the form
        $studentName = '';

        // Get the student's name based on their ID
        foreach ($students as $student) {
            if ($student['ID'] == $id) {
                $studentName = $student['Name'];
                break;
            }
        }

        // Valid status options
        $validStatuses = ['present', 'absent', 'excused'];
        if (in_array($status, $validStatuses)) {
            // Update the attendance status in the student's file
            updateAttendanceStatus($id, $status, $date, $studentName);
        } else {
            echo "Invalid status!";
        }

        // Redirect back to the admin page
        header('Location: admin_dashboard.php?date=' . $date); // Retain the selected date in the URL
        exit();
    }

    // Retain the selected date from the GET parameter (if exists)
    $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('M d, Y'); // Default to current date if not set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance</title>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="flex min-h-screen bg-gray-100">

    <!-- Sidebar -->
    <div class="w-64 bg-gray-800 text-white p-4 flex flex-col items-center">
        <div class="text-xl font-semibold mb-8 text-center">Attendance Monitoring System</div>
        <div class="flex flex-col space-y-4 w-full">
            <!-- Manage Attendance Button -->
            <form action="admin_dashboard.php" method="GET" class="w-full">
                <button class="w-full px-4 py-3 rounded bg-blue-500 hover:bg-blue-600 text-center">Manage Attendance</button>
            </form>
            <!-- Manage Students Button -->
            <form action="manage_student.php" method="GET" class="w-full">
                <button class="w-full px-4 py-3 rounded bg-gray-700 hover:bg-teal-500 text-center">Manage Students</button>
            </form>
            <!-- Manage Appeals Button -->
            <form action="manage_appeal.php" method="GET" class="w-full">
                <button class="w-full px-4 py-3 rounded bg-gray-700 hover:bg-teal-500 text-center">Manage Appeals</button>
            </form>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex-1 p-6">
        <!-- Top Bar -->
        <div class="bg-gray-700 text-white flex justify-between items-center p-4 mb-6 rounded-lg">
            <h1 class="text-2xl">Welcome <?php echo htmlspecialchars($_SESSION['role']) . " " . htmlspecialchars($_SESSION['name']); ?>!</h1>
            <a href="admin_dashboard.php?logout=true">
                <button class="bg-red-500 text-white py-2 px-6 rounded hover:bg-red-600">Sign Out</button>
            </a>
        </div>

        <!-- Attendance Content -->
        <div class="space-y-6">
            <!-- Date picker section -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-semibold">Set Attendance Date</h2>
                </div>
                <form action="admin_dashboard.php" method="GET" class="flex space-x-2">
                    <input type="text" id="attendance_date" name="date" placeholder="Select Date" value="<?php echo htmlspecialchars($selectedDate); ?>" class="px-4 py-2 rounded border border-gray-300" required>
                    <button type="submit" class="px-6 py-2 bg-gray-700 text-white rounded hover:bg-teal-500">Set Date</button>
                </form>
            </div>

            <!-- Student List Table -->
            <h2 class="text-xl font-semibold">Student List</h2>
            <table class="w-full table-auto border-collapse border border-gray-300 mt-4">
                <thead>
                    <tr class="bg-gray-700 text-white">
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Student Name</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="border px-4 py-2"><?php echo $student['ID']; ?></td>
                            <td class="border px-4 py-2"><?php echo $student['Name']; ?></td>
                            <td class="border px-4 py-2 flex space-x-2">
                                <!-- Present button -->
                                <form action="admin_dashboard.php" method="GET" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $student['ID']; ?>">
                                    <input type="hidden" name="status" value="present">
                                    <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
                                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Present</button>
                                </form>

                                <!-- Absent button -->
                                <form action="admin_dashboard.php" method="GET" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $student['ID']; ?>">
                                    <input type="hidden" name="status" value="absent">
                                    <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
                                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Absent</button>
                                </form>

                                <!-- Excused button -->
                                <form action="admin_dashboard.php" method="GET" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $student['ID']; ?>">
                                    <input type="hidden" name="status" value="excused">
                                    <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
                                    <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Excused</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Initialize Flatpickr date picker -->
    <script>
        flatpickr("#attendance_date", {
            dateFormat: "M d, Y", // Format: MMM dd, yyyy
            defaultDate: "<?php echo $selectedDate; ?>", // Set default date to the selected or current date
            enableTime: true,
            time_24hr: true
        });
    </script>
</body>
</html>
