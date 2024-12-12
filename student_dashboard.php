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
            while (($data = fgetcsv($handle)) !== FALSE) {
                list($id, $name, $role, $password) = $data;
                if ($role === 'Student') {
                    $students[] = ['ID' => $id, 'Name' => $name, 'Password' => $password];
                }
            }
            fclose($handle);
        }
        return $students;
    }

    // Function to save the appeal in a uniquely named CSV file
    function saveAppeal($attendanceID, $studentID, $studentName, $date, $reason) {
        // Construct the file name as {studentID}_{date}.csv
        $appealFile = "assets/data/appeal_data/{$studentID}_{$date}.csv";

        // Prepare the data to be saved in CSV format
        $appealData = [$attendanceID, $studentID, $studentName, $date, $reason];

        // Open the file for appending and save the appeal data
        if (($handle = fopen($appealFile, 'a')) !== FALSE) {
            fputcsv($handle, $appealData);
            fclose($handle);
        }
    }

    // Handle sign out logic
    if (isset($_GET['logout'])) {
        // Destroy the session and redirect to the login page
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Handle appeal submission logic
    if (isset($_POST['appeal'])) {
        $attendanceID = $_POST['attendance_id'];
        $studentID = $_SESSION['id']; // Get the student ID from the session
        $studentName = $_SESSION['name']; // Get the student Name from the session
        $date = $_POST['date'];
        $reason = $_POST['reason'];

        // Save the appeal in the file with the desired naming format
        saveAppeal($attendanceID, $studentID, $studentName, $date, $reason);

        // Redirect to prevent form resubmission
        header("Location: student_dashboard.php");
        exit();
    }

    // Get students data
    $students = getStudentsFromCSV('assets/data/users.csv');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex min-h-screen bg-gray-100">

    <!-- Sidebar -->
    <div class="w-64 bg-gray-800 text-white p-4 flex flex-col items-center">
        <div class="text-xl font-semibold mb-8 text-center">Attendance Monitoring System</div>
        <div class="flex flex-col space-y-4 w-full">
            <!-- Student Attendance Record Button -->
            <form action="student_dashboard.php" method="GET" class="w-full">
                <button class="w-full px-4 py-3 rounded bg-blue-500 hover:bg-blue-600 text-center">Your Attendance Record</button>
            </form>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex-1 p-6">
        <!-- Top Bar -->
        <div class="bg-gray-700 text-white flex justify-between items-center p-4 mb-6 rounded-lg">
            <h1 class="text-2xl">Welcome <?php echo htmlspecialchars($_SESSION['role']) . " " . htmlspecialchars($_SESSION['name']); ?>!</h1>
            <a href="student_dashboard.php?logout=true">
                <button class="bg-red-500 text-white py-2 px-6 rounded hover:bg-red-600">Sign Out</button>
            </a>
        </div>

        <!-- Attendance Content -->
        <div class="space-y-6">
            <!-- Attendance Record Table -->
            <h2 class="text-xl font-semibold">Your Attendance Record</h2>
            <table id="attendance-table" class="min-w-full table-auto bg-white shadow-md rounded-lg">
                <thead>
                    <tr class="bg-gray-200 text-gray-800">
                        <th class="px-4 py-2 border">Date</th>
                        <th class="px-4 py-2 border">Status</th>
                        <th class="px-4 py-2 border">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $studentID = $_SESSION['id'];
                    $filePath = "assets/data/student_data/{$studentID}.csv";

                    if (file_exists($filePath)) {
                        $file = fopen($filePath, 'r');

                        while (($row = fgetcsv($file)) !== false) {
                            // Ensure the data matches the format: Attendance ID, ID, Student Name, Date, Status
                            if (count($row) === 5) {
                                $attendanceID = htmlspecialchars($row[0]);
                                $studentName = htmlspecialchars($row[2]);
                                $date = htmlspecialchars($row[3]);
                                $status = strtolower(htmlspecialchars($row[4])); // Lowercase for consistent comparison

                                // Check if an appeal already exists for this date
                                $appealFile = "assets/data/appeal_data/{$studentID}_{$date}.csv";
                                $appealPending = file_exists($appealFile);
                                ?>
                                <tr>
                                    <td class="border px-4 py-2"><?php echo $date; ?></td>
                                    <td class="border px-4 py-2"><?php echo ucfirst($status); ?></td>
                                    <td class="border px-4 py-2">
                                        <?php if ($status === 'absent'): ?>
                                            <?php if ($appealPending): ?>
                                                <span class="text-yellow-500">Pending</span>
                                            <?php else: ?>
                                                <button 
                                                    class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600" 
                                                    data-attendance-id="<?php echo $attendanceID; ?>" 
                                                    data-date="<?php echo $date; ?>" onclick="openAppealForm('<?php echo $attendanceID; ?>', '<?php echo $date; ?>')">
                                                    Appeal
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray-500">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }

                        fclose($file);
                    } else {
                        echo "<tr><td colspan='3' class='text-center py-4'>No attendance records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Appeal Form Modal -->
    <div id="appeal-form" class="fixed inset-0 flex justify-center items-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg w-1/3">
            <h2 class="text-xl font-semibold mb-4">Submit an Appeal</h2>
            <form method="POST">
                <input type="hidden" name="attendance_id" id="appeal-attendance-id">
                <input type="hidden" name="date" id="appeal-date">
                <textarea name="reason" id="appeal-reason" placeholder="Enter your reason here..." class="w-full p-2 border rounded mb-4" required></textarea>
                <div class="flex justify-between">
                    <button type="button" id="cancel-appeal" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500" onclick="closeAppealForm()">Cancel</button>
                    <button type="submit" name="appeal" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open the appeal form modal
        function openAppealForm(attendanceId, date) {
            document.getElementById('appeal-attendance-id').value = attendanceId;
            document.getElementById('appeal-date').value = date;
            document.getElementById('appeal-form').classList.remove('hidden');
        }

        // Close the appeal form modal
        function closeAppealForm() {
            document.getElementById('appeal-form').classList.add('hidden');
        }
    </script>
</body>
</html>
