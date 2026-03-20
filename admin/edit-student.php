<?php
session_start();
include '../dbconfig.php';

$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Admin';

$message = '';
$message_type = '';
$student = null;

// Check if ID is provided
if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $fetch_sql = "SELECT * FROM students WHERE id = $student_id";
    $fetch_result = $connection->query($fetch_sql);
    
    if ($fetch_result && $fetch_result->num_rows > 0) {
        $student = $fetch_result->fetch_assoc();
    } else {
        $message = "Student not found!";
        $message_type = "danger";
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $student) {
    $student_id = intval($_POST['student_id']);
    $roll_no = $connection->real_escape_string($_POST['roll_no']);
    $name = $connection->real_escape_string($_POST['name']);
    $class = $connection->real_escape_string($_POST['class']);
    $email = $connection->real_escape_string($_POST['email']);
    $phone = $connection->real_escape_string($_POST['phone']);
    $status = $connection->real_escape_string($_POST['status']);
    
    // Validation
    if (empty($roll_no) || empty($name) || empty($class)) {
        $message = "Please fill in all required fields!";
        $message_type = "danger";
    } else {
        // Check if roll_no already exists (excluding current student)
        $check_sql = "SELECT id FROM students WHERE roll_no = '$roll_no' AND id != $student_id";
        $check_result = $connection->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $message = "Roll number already exists!";
            $message_type = "danger";
        } else {
            $update_sql = "UPDATE students SET roll_no = '$roll_no', name = '$name', class = '$class', email = '$email', phone = '$phone', status = '$status' WHERE id = $student_id";
            
            if ($connection->query($update_sql) === TRUE) {
                $message = "Student updated successfully!";
                $message_type = "success";
                // Refresh student data
                $fetch_sql = "SELECT * FROM students WHERE id = $student_id";
                $fetch_result = $connection->query($fetch_sql);
                $student = $fetch_result->fetch_assoc();
            } else {
                $message = "Error: " . $connection->error;
                $message_type = "danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Student</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fcfbfb; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-bottom: 3px solid #f7d794; }
    .header h2 { color: #192a56; margin: 0; font-weight: 700; }
    .form-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 3px solid #f7d794; }
    .form-label { color: #192a56; font-weight: 600; margin-bottom: 8px; }
    .form-control, .form-select { padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; }
    .form-control:focus, .form-select:focus { border-color: #f7d794; box-shadow: 0 0 0 3px rgba(247,215,148,0.25); }
    .btn-submit { background: #f7d794; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; }
    .btn-submit:hover { background: #e5c682; }
    .btn-cancel { background: #e2e8f0; color: #192a56; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; margin-left: 10px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-edit"></i> Edit Student</h2>
    </div>
    
    <div class="form-card">
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
          <?php echo $message; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <?php if ($student): ?>
        <form method="POST" action="">
          <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Roll Number *</label>
              <input type="text" class="form-control" name="roll_no" required value="<?php echo htmlspecialchars($student['roll_no']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Student Name *</label>
              <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($student['name']); ?>">
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Class *</label>
              <input type="text" class="form-control" name="class" placeholder="e.g., Grade 10A" required value="<?php echo htmlspecialchars($student['class']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone Number</label>
              <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="Active" <?php echo ($student['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo ($student['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
          </div>
          
          <div class="mt-4">
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Student</button>
            <a href="students.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-danger">Student not found. <a href="students.php">Go back to students list</a></div>
      <?php endif; ?>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
