<?php
session_start();
include '../dbconfig.php';

$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Admin';

$message = '';
$message_type = '';

// Handle Delete Operation
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $delete_sql = "DELETE FROM students WHERE id = $student_id";
    
    if ($connection->query($delete_sql) === TRUE) {
        $message = "Student deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting student: " . $connection->error;
        $message_type = "danger";
    }
}

// Fetch all students from database
$sql = "SELECT * FROM students";
$search = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $connection->real_escape_string($_GET['search']);
    $sql = "SELECT * FROM students WHERE roll_no LIKE '%$search%' OR name LIKE '%$search%' OR class LIKE '%$search%'";
}

$result = $connection->query($sql);
$students = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Students</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/theme.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
    .main-content { margin-left: 280px; padding: 30px; }
    .header { background: white; padding: 20px 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .header h2 { color: #2c3e50; margin: 0; font-weight: 700; }
    .btn-add { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 8px; }
    .btn-add:hover { background: #2980b9; }
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    .search-box { margin-bottom: 20px; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-user-graduate"></i> Manage Students</h2>
      <button class="btn-add" onclick="window.location.href='add-student.php'"><i class="fas fa-plus"></i> Add New Student</button>
    </div>
    
    <?php if ($message): ?>
      <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <div class="content-card">
      <div class="search-box">
        <form method="GET" action="">
          <input type="text" class="form-control" name="search" placeholder="Search students by name, roll number, or class..." value="<?php echo htmlspecialchars($search); ?>">
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Student Name</th>
              <th>Class</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($students) > 0): ?>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                  <td><?php echo htmlspecialchars($student['name']); ?></td>
                  <td><?php echo htmlspecialchars($student['class']); ?></td>
                  <td><?php echo htmlspecialchars($student['email']); ?></td>
                  <td><?php echo htmlspecialchars($student['phone']); ?></td>
                  <td>
                    <?php 
                      $status = isset($student['status']) ? $student['status'] : 'Active';
                      $badge_class = ($status == 'Active') ? 'bg-success' : 'bg-danger';
                    ?>
                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                  </td>
                  <td>
                    <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    <a href="students.php?action=delete&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this student?');"><i class="fas fa-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No students found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
