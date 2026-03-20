<?php
session_start();
include '../dbconfig.php';

$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Admin';

$message = '';
$message_type = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        // Check if roll_no already exists
        $check_sql = "SELECT id FROM students WHERE roll_no = '$roll_no'";
        $check_result = $connection->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $message = "Roll number already exists!";
            $message_type = "danger";
        } else {
            $sql = "INSERT INTO students (roll_no, name, class, email, phone, status) 
                    VALUES ('$roll_no', '$name', '$class', '$email', '$phone', '$status')";
            
            if ($connection->query($sql) === TRUE) {
                $message = "Student added successfully!";
                $message_type = "success";
                // Redirect after 2 seconds
                header("refresh:2;url=students.php");
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
  <title>Add New Student</title>
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
      <h2><i class="fas fa-user-plus"></i> Add New Student</h2>
    </div>
    
    <div class="form-card">
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
          <?php echo $message; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <form method="POST" action="">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Roll Number *</label>
            <input type="text" class="form-control" name="roll_no" required value="<?php echo isset($_POST['roll_no']) ? htmlspecialchars($_POST['roll_no']) : ''; ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Student Name *</label>
            <input type="text" class="form-control" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Class *</label>
            <input type="text" class="form-control" name="class" placeholder="e.g., Grade 10A" required value="<?php echo isset($_POST['class']) ? htmlspecialchars($_POST['class']) : ''; ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number</label>
            <input type="tel" class="form-control" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="Active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
              <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Student</button>
          <a href="students.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>
