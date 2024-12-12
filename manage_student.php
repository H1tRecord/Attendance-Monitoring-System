<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['id']) || !isset($_SESSION['name'])) {
        header('Location: login.php'); // Redirect to login page if not logged in
        exit;
    }

    // Function to read users.csv and return an array of all users (students and admins)
    function getUsersFromCSV($file) {
        $users = [];
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                list($id, $name, $role, $password) = $data;
                $users[] = ['ID' => trim($id), 'Name' => $name, 'Role' => $role, 'Password' => $password];
            }
            fclose($handle);
        }
        return $users;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle student add
        if (isset($_POST['add_student'])) {
            $id = trim($_POST['id']);
            $name = $_POST['name'];
            $password = $_POST['password'];
        
            // Validate that ID is unique for students
            $users = getUsersFromCSV('assets/data/users.csv');
            $isDuplicate = false;
        
            foreach ($users as $user) {
                if ($user['ID'] === $id) {
                    $isDuplicate = true;
                    break;
                }
            }
        
            if ($isDuplicate) {
                // Set a session variable to show the error message
                $_SESSION['error_message'] = 'Student ID must be unique!';
                header("Location: manage_student.php");
                exit;
            } else {
                // Add the new student to the CSV
                $file = fopen('assets/data/users.csv', 'a');
                fputcsv($file, [$id, $name, 'Student', $password]);
                fclose($file);
        
                // Create a new file for the student in the student_data folder
                $student_file_path = 'assets/data/student_data/' . $id . '.csv';
                $student_file = fopen($student_file_path, 'w');
                fclose($student_file); // Create an empty file
        
                header("Location: manage_student.php");
                exit;
            }
        }

        // Handle student update (edit)
        if (isset($_POST['edit_student'])) {
            $id = trim($_POST['id']);
            $name = $_POST['name'];
            $password = $_POST['password'];
        
            // Read all users data from CSV
            $users = getUsersFromCSV('assets/data/users.csv');
        
            // Create a new array to store updated users
            $updated_users = [];
            $student_updated = false;
        
            // Go through each user and update the specific student
            foreach ($users as $user) {
                // Check if this is the student to update (matching ID and Role)
                if ($user['ID'] === $id && $user['Role'] === 'Student') {
                    // Create an updated user record
                    $updated_user = [
                        $id,        // ID
                        $name,      // Updated name
                        'Student',  // Role
                        $password   // Updated password
                    ];
                    $updated_users[] = $updated_user;
                    $student_updated = true;
                } else {
                    // Keep other users as they are
                    $updated_users[] = [
                        $user['ID'], 
                        $user['Name'], 
                        $user['Role'], 
                        $user['Password']
                    ];
                }
            }
        
            // If student was found and updated, write back to CSV
            if ($student_updated) {
                $file = fopen('assets/data/users.csv', 'w');
                foreach ($updated_users as $user) {
                    fputcsv($file, $user);
                }
                fclose($file);
            }
        
            header("Location: manage_student.php"); // Refresh the page after editing
            exit;
        }

        // Handle student deletion
        if (isset($_POST['delete_student'])) {
            $id = trim($_POST['id_to_delete']);

            // Read all users data from CSV
            $users = getUsersFromCSV('assets/data/users.csv');

            // Filter out the student to delete (keeping admins intact)
            $users = array_filter($users, function($user) use ($id) {
                return !($user['ID'] === $id && $user['Role'] === 'Student'); // Delete only students
            });

            // Reindex the array after filtering
            $users = array_values($users);

            // Write the updated users back to the CSV (overwriting the file)
            $file = fopen('assets/data/users.csv', 'w');
            foreach ($users as $user) {
                fputcsv($file, [$user['ID'], $user['Name'], $user['Role'], $user['Password']]);
            }
            fclose($file);

            // Delete the student's CSV file from student_data
            $student_file = 'assets/data/student_data/' . $id . '.csv';
            if (file_exists($student_file)) {
                unlink($student_file);
            }

            // Delete all appeal-related CSV files from appeal_data (files matching {id}_*.csv)
            $appeal_files = glob('assets/data/appeal_data/' . $id . '_*.csv');
            foreach ($appeal_files as $file) {
                unlink($file); // Delete each file that matches the pattern
            }

            header("Location: manage_student.php"); // Refresh the page after deletion
            exit;
        }
    }

    // Get all users data
    $users = getUsersFromCSV('assets/data/users.csv');
    $students = array_filter($users, function($user) {
        return $user['Role'] === 'Student'; // Only include students
    });

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
    <title>Manage Students</title>
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
                <button class="w-full px-4 py-3 rounded bg-blue-500 hover:bg-blue-600 text-center">Manage Students</button>
            </form>
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
            <a href="manage_student.php?logout=true">
                <button class="bg-red-500 text-white py-2 px-6 rounded hover:bg-red-600">Sign Out</button>
            </a>
        </div>

        <!-- Add Student Button -->
        <div class="mb-6 flex justify-end">
            <button onclick="openAddStudentModal()" class="bg-blue-500 text-white py-2 px-6 rounded hover:bg-blue-600">Add Student</button>
        </div>

        <!-- Student List Table -->
        <table class="w-full table-auto border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-700 text-white">
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Student Name</th>
                    <th class="px-4 py-2">Password</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="border px-4 py-2"><?php echo $student['ID']; ?></td>
                        <td class="border px-4 py-2"><?php echo $student['Name']; ?></td>
                        <td class="border px-4 py-2"><?php echo $student['Password']; ?></td>
                        <td class="border px-4 py-2">
                            <button onclick="openEditStudentModal('<?php echo $student['ID']; ?>', '<?php echo $student['Name']; ?>', '<?php echo $student['Password']; ?>')" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit</button>
                            <button onclick="openDeleteConfirmationModal('<?php echo $student['ID']; ?>')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Adding Student Modal -->
    <div id="addStudentModal" class="fixed inset-0 flex justify-center items-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg w-1/3">
            <h2 class="text-xl mb-4">Add Student</h2>
            <form action="manage_student.php" method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700" for="id">Student ID</label>
                    <input type="text" name="id" id="id" required class="mt-1 px-4 py-2 border border-gray-300 rounded w-full">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700" for="name">Student Name</label>
                    <input type="text" name="name" id="name" required class="mt-1 px-4 py-2 border border-gray-300 rounded w-full">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700" for="password">Password</label>
                    <input type="password" name="password" id="password" required class="mt-1 px-4 py-2 border border-gray-300 rounded w-full">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeAddStudentModal()" class="px-4 py-2 bg-gray-500 text-white rounded">Cancel</button>
                    <button type="submit" name="add_student" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Editing Student Modal -->
    <div id="editStudentModal" class="fixed inset-0 flex justify-center items-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg w-1/3">
            <h2 class="text-xl mb-4">Edit Student</h2>
            <form action="manage_student.php" method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700" for="name">Student Name</label>
                    <input type="text" name="name" id="edit_name" required class="mt-1 px-4 py-2 border border-gray-300 rounded w-full">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700" for="password">Password</label>
                    <input type="password" name="password" id="edit_password" required class="mt-1 px-4 py-2 border border-gray-300 rounded w-full">
                </div>
                <input type="hidden" name="id" id="edit_id">
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeEditStudentModal()" class="px-4 py-2 bg-gray-500 text-white rounded">Cancel</button>
                    <button type="submit" name="edit_student" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deleting Student Modal -->
    <div id="deleteConfirmationModal" class="fixed inset-0 flex justify-center items-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg w-1/3">
            <h2 class="text-xl mb-4">Are you sure?</h2>
            <p class="mb-4">This action will delete the student permanently.</p>
            <form action="manage_student.php" method="POST">
                <input type="hidden" name="delete_student" value="true">
                <input type="hidden" name="id_to_delete" id="id_to_delete">
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeDeleteConfirmationModal()" class="px-4 py-2 bg-gray-500 text-white rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddStudentModal() {
            document.getElementById("addStudentModal").style.display = 'flex';
        }

        function closeAddStudentModal() {
            document.getElementById("addStudentModal").style.display = 'none';
        }

        function openEditStudentModal(id, name, password) {
            document.getElementById("editStudentModal").style.display = 'flex';
            document.getElementById("edit_id").value = id;  // Set the ID for hidden input
            document.getElementById("edit_name").value = name;  // Set name for input
            document.getElementById("edit_password").value = password;  // Set password for input
        }

        function closeEditStudentModal() {
            document.getElementById("editStudentModal").style.display = 'none';
        }

        function openDeleteConfirmationModal(id) {
            document.getElementById("deleteConfirmationModal").style.display = 'flex';
            document.getElementById("id_to_delete").value = id;
        }

        function closeDeleteConfirmationModal() {
            document.getElementById("deleteConfirmationModal").style.display = 'none';
        }

        <?php 
            if (isset($_SESSION['error_message'])) {
                echo "alert('" . $_SESSION['error_message'] . "');";
                // Clear the error message
                unset($_SESSION['error_message']);
            }
        ?>
    </script>
</body>
</html>
