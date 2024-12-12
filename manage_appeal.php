<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['id']) || !isset($_SESSION['name'])) {
        header('Location: login.php'); // Redirect to login page if not logged in
        exit;
    }

    // Function to read appeal data from a given appeal file
    function getAppealsFromDirectory($directory) {
        $appeals = [];
        $files = glob($directory . '/*.csv'); // Get all CSV files in the appeal_data directory

        foreach ($files as $file) {
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) === 5) {
                        // Format: Attendance ID, ID, Student Name, Date, Reason
                        $appeals[] = [
                            'attendance_id' => $data[0],
                            'student_id' => $data[1],
                            'student_name' => $data[2],
                            'date' => $data[3],
                            'reason' => $data[4],
                            'file' => $file // Store the appeal file for deletion later
                        ];
                    }
                }
                fclose($handle);
            }
        }
        return $appeals;
    }

    // Function to approve the appeal (update student data and mark as excused)
    function approveAppeal($attendanceID, $studentID, $appealFile) {
        $studentFile = "assets/data/student_data/{$studentID}.csv";
        
        if (file_exists($studentFile)) {
            $rows = [];
            $file = fopen($studentFile, 'r');
            
            while (($row = fgetcsv($file)) !== FALSE) {
                if ($row[0] === $attendanceID) {
                    $row[4] = 'excused'; // Change the status to 'excused'
                }
                $rows[] = $row;
            }
            fclose($file);

            // Rewrite the file with updated data
            $file = fopen($studentFile, 'w');
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }

        // Delete the appeal file after approval
        if (file_exists($appealFile)) {
            unlink($appealFile); // Delete the appeal file
        }
    }

    // Function to reject the appeal (delete the appeal file)
    function rejectAppeal($appealFile) {
        if (file_exists($appealFile)) {
            unlink($appealFile); // Delete the appeal file
        }
    }

    // Handle approval or rejection
    if (isset($_POST['action'])) {
        $attendanceID = $_POST['attendance_id'];
        $studentID = $_POST['student_id'];
        $appealFile = $_POST['appeal_file'];

        if ($_POST['action'] === 'approve') {
            approveAppeal($attendanceID, $studentID, $appealFile);
        } elseif ($_POST['action'] === 'reject') {
            rejectAppeal($appealFile);
        }

        // Redirect to the same page to refresh the appeal list
        header("Location: manage_appeal.php");
        exit();
    }

    // Get the list of appeals
    $appeals = getAppealsFromDirectory('assets/data/appeal_data');

    // Sign out logic
    if (isset($_GET['logout'])) {
        session_unset();
        session_destroy();
        header("Location: login.php"); // Redirect to login page after logout
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appeals</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="flex min-h-screen bg-gray-100">

    <!-- Sidebar -->
    <div class="w-64 bg-gray-800 text-white p-4 flex flex-col items-center">
        <div class="text-xl font-semibold mb-8 text-center">Attendance Monitoring System</div>
        <div class="flex flex-col space-y-4 w-full">
            <form action="admin_dashboard.php" method="GET" class="w-full">
                <button class="w-full px-4 py-3 rounded bg-gray-700 hover:bg-teal-500 text-center">Manage Attendance</button>
            </form>
            <form action="manage_student.php" method="GET" class="w-full">
                <button class="w-full px-4 py-3 rounded bg-gray-700 hover:bg-teal-500 text-center">Manage Students</button>
            </form>
            <form action="manage_appeal.php" method="GET" class="w-full">
                <button class="w-full px-4 py-3 rounded bg-blue-500 hover:bg-blue-600 text-center">Manage Appeals</button>
            </form>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex-1 p-6">
        <!-- Top Bar -->
        <div class="bg-gray-700 text-white flex justify-between items-center p-4 mb-6 rounded-lg">
            <h1 class="text-2xl">Welcome <?php echo htmlspecialchars($_SESSION['role']) . " " . htmlspecialchars($_SESSION['name']); ?>!</h1>
            <a href="manage_appeal.php?logout=true">
                <button class="bg-red-500 text-white py-2 px-6 rounded hover:bg-red-600">Sign Out</button>
            </a>
        </div>

        <!-- Appeals List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (count($appeals) > 0): ?>
                <?php foreach ($appeals as $appeal): ?>
                    <div class="bg-white p-4 rounded-lg shadow-lg">
                        <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($appeal['student_name']); ?></h3>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($appeal['date']); ?></p>
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($appeal['reason']); ?></p>

                        <form action="manage_appeal.php" method="POST" class="mt-4">
                            <input type="hidden" name="attendance_id" value="<?php echo htmlspecialchars($appeal['attendance_id']); ?>">
                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($appeal['student_id']); ?>">
                            <input type="hidden" name="appeal_file" value="<?php echo htmlspecialchars($appeal['file']); ?>">
                            <button type="submit" name="action" value="approve" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Approve</button>
                            <button type="submit" name="action" value="reject" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Reject</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white p-4 rounded-lg shadow-lg col-span-3">
                    <p class="text-xl font-semibold text-center">There are no appeals at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
