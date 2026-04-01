<?php
// Admin page for listing and managing students.
require_once __DIR__ . '/auth.php';
include '../includes/db_connect.php';
require_once __DIR__ . '/db_helpers.php';

// Handle Create Action
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $roll_no = $conn->real_escape_string($_POST['roll_no']);
    $name = $conn->real_escape_string($_POST['name']);
    $class = $conn->real_escape_string($_POST['class']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $status = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

    // Check if roll_no already exists
    $check_sql = "SELECT id FROM students WHERE roll_no = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $roll_no);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        setcookie('error', 'Roll number already exists!', time() + 5);
    } else {
        $insert_sql = "INSERT INTO students (roll_no, name, class, email, phone, status) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssss", $roll_no, $name, $class, $email, $phone, $status);

        if ($insert_stmt->execute()) {
            setcookie('success', 'Student added successfully!', time() + 5);
        } else {
            setcookie('error', 'Failed to add student. Please try again.', time() + 5);
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Update Action
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $student_id = intval($_POST['student_id']);
    $roll_no = $conn->real_escape_string($_POST['roll_no']);
    $name = $conn->real_escape_string($_POST['name']);
    $class = $conn->real_escape_string($_POST['class']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $status = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

    // Check if roll_no already exists (excluding current student)
    $check_sql = "SELECT id FROM students WHERE roll_no = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $roll_no, $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        setcookie('error', 'Roll number already exists!', time() + 5);
    } else {
        $update_sql = "UPDATE students SET roll_no = ?, name = ?, class = ?, email = ?, phone = ?, status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssi", $roll_no, $name, $class, $email, $phone, $status, $student_id);

        if ($update_stmt->execute()) {
            setcookie('success', 'Student updated successfully!', time() + 5);
        } else {
            setcookie('error', 'Failed to update student. Please try again.', time() + 5);
        }
        $update_stmt->close();
    }
    $check_stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Delete Action
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
  $student_id = intval($_POST['student_id'] ?? 0);
  $delete_error = '';
  $transaction_started = false;

  if ($student_id <= 0) {
    $delete_error = 'Invalid student record selected for deletion.';
  } else {
    if ($conn->begin_transaction()) {
      $transaction_started = true;
    }

    $nullify_targets = [
      ['attendance', 'student_id', true],
      ['grades', 'student_id', true],
      ['marks', 'student_id', true],
      ['fees', 'student_id', true],
      ['assignment_submissions', 'student_id', true],
      ['leave_applications', 'student_id', true],
    ];

    foreach ($nullify_targets as $target) {
      [$table_name, $column_name, $allow_delete_fallback] = $target;
      $reference_error = '';

      if (!admin_clear_reference($conn, $table_name, $column_name, $student_id, $allow_delete_fallback, $reference_error)) {
        $delete_error = 'Unable to clear related student data in ' . $table_name . '.';
        if ($reference_error !== '') {
          $delete_error .= ' ' . $reference_error;
        }
        break;
      }
    }

    if ($delete_error === '') {
      $delete_sql = "DELETE FROM students WHERE id = ?";
      $delete_stmt = $conn->prepare($delete_sql);

      if (!$delete_stmt) {
        $delete_error = 'Unable to process student delete request.';
      } else {
        $delete_stmt->bind_param("i", $student_id);

        if (!$delete_stmt->execute()) {
          $delete_error = 'Failed to delete student. Please try again.';
        } elseif ($delete_stmt->affected_rows < 1) {
          $delete_error = 'Student record was not found.';
        }

        $delete_stmt->close();
      }
    }

    if ($delete_error === '' && $transaction_started && !$conn->commit()) {
      $delete_error = 'Unable to finalize student deletion. Please try again.';
    }
  }

  if ($delete_error !== '') {
    if ($transaction_started) {
      $conn->rollback();
    }
    setcookie('error', $delete_error, time() + 5);
  } else {
    setcookie('success', 'Student deleted successfully!', time() + 5);
  }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

  // Build the student list query with optional search filter.
// Fetch all students
$sql = "SELECT * FROM students ORDER BY id DESC";
$search = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $sql = "SELECT * FROM students WHERE roll_no LIKE '%$search%' OR name LIKE '%$search%' OR class LIKE '%$search%' ORDER BY id DESC";
}

$result = $conn->query($sql);
$students = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!-- Render student table, action modals, and form interactions. -->
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
    .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .table th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
    .search-box { margin-bottom: 20px; }
    
    .modal-content {
      height: auto;
      border: none;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .modal-body {
      max-height: calc(80vh - 130px);
      overflow-y: auto;
      padding: 20px;
    }
    
    /* Prevent flickering on form elements */
    .form-control, .form-select {
      background-color: #fff;
      border: 1px solid #ced4da;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #80bdff;
      outline: 0;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .create-student-btn,
    .create-student-btn:link,
    .create-student-btn:visited {
      background-color: #0d6efd !important;
      border-color: #0d6efd !important;
      color: #ffffff !important;
      text-decoration: none !important;
    }

    .create-student-btn i {
      color: #ffffff !important;
    }

    .create-student-btn:hover,
    .create-student-btn:focus,
    .create-student-btn:active {
      background-color: #0b5ed7 !important;
      border-color: #0a58ca !important;
      color: #ffffff !important;
      text-decoration: none !important;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
  <div class="main-content">
    <div class="header">
      <h2><i class="fas fa-user-graduate"></i> Manage Students</h2>
      <a href="add-student.php" class="btn create-student-btn">
        <i class="fas fa-plus"></i> Add New Student
      </a>
    </div>
    
    <!-- Display success and error Messages -->
    <?php if (isset($_COOKIE['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_COOKIE['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_COOKIE['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_COOKIE['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" class="h-100 d-flex flex-column" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">

                        <div class="mb-3">
                            <label class="form-label">Roll Number *</label>
                            <input type="text" class="form-control" name="roll_no" required placeholder="e.g., STU001">
                            <small class="text-danger d-none"></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Student Name *</label>
                            <input type="text" class="form-control" name="name" required placeholder="e.g., John Doe">
                            <small class="text-danger d-none"></small>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Class *</label>
                                <input type="text" class="form-control" name="class" placeholder="e.g., Grade 10A" required>
                                <small class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="Active" selected>Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <small class="text-danger d-none"></small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="student@school.com">
                                <small class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" placeholder="+1 234 567 8900">
                                <small class="text-danger d-none"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer mt-auto">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="content-card">
      <div class="search-box">
        <form method="GET" action="" class="mb-3" novalidate>
          <input type="text" class="form-control" name="search" placeholder="Search students by name, roll number, or class..." 
            value="<?php echo htmlspecialchars($search); ?>">
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Student Name</th>
              <th>Class</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Actions</th>
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
                    <button
                      class="btn btn-sm btn-outline-primary js-view-student"
                      data-bs-toggle="modal"
                      data-bs-target="#viewStudentModal"
                      data-id="<?php echo (int) $student['id']; ?>"
                      data-roll-no="<?php echo htmlspecialchars($student['roll_no'], ENT_QUOTES); ?>"
                      data-name="<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>"
                      data-class="<?php echo htmlspecialchars($student['class'], ENT_QUOTES); ?>"
                      data-email="<?php echo htmlspecialchars((string) ($student['email'] ?? ''), ENT_QUOTES); ?>"
                      data-phone="<?php echo htmlspecialchars((string) ($student['phone'] ?? ''), ENT_QUOTES); ?>"
                      data-status="<?php echo htmlspecialchars((string) ($student['status'] ?? 'Active'), ENT_QUOTES); ?>">
                      <i class="fas fa-eye"></i>
                    </button>
                    <a
                      class="btn btn-sm btn-outline-warning"
                      href="edit-student.php?id=<?php echo (int) $student['id']; ?>">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button
                      class="btn btn-sm btn-outline-danger js-delete-student"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteStudentModal"
                      data-id="<?php echo (int) $student['id']; ?>"
                      data-name="<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>">
                      <i class="fas fa-trash"></i>
                    </button>
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

    <!-- View Student Modal -->
    <div class="modal fade" id="viewStudentModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Student Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p><strong>Roll No:</strong> <span id="view-roll-no"></span></p>
            <p><strong>Name:</strong> <span id="view-name"></span></p>
            <p><strong>Class:</strong> <span id="view-class"></span></p>
            <p><strong>Email:</strong> <span id="view-email"></span></p>
            <p><strong>Phone:</strong> <span id="view-phone"></span></p>
            <p><strong>Status:</strong> <span id="view-status"></span></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="post" novalidate>
            <div class="modal-header">
              <h5 class="modal-title">Edit Student</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="student_id" id="edit-student-id">

              <div class="mb-3">
                <label class="form-label">Roll Number *</label>
                <input type="text" class="form-control" name="roll_no" id="edit-roll-no" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Student Name *</label>
                <input type="text" class="form-control" name="name" id="edit-name" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Class *</label>
                  <input type="text" class="form-control" name="class" id="edit-class" placeholder="e.g., Grade 10A" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status" id="edit-status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" name="email" id="edit-email">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone Number</label>
                  <input type="tel" class="form-control" name="phone" id="edit-phone">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Student</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Student Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" novalidate>
            <div class="modal-header">
              <h5 class="modal-title">Confirm Delete</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to delete <strong id="delete-student-name"></strong>?</p>
              <p class="text-danger">This action cannot be undone.</p>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="student_id" id="delete-student-id">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const toText = (value) => value ? value : '-';

      document.querySelectorAll('.js-view-student').forEach((btn) => {
        btn.addEventListener('click', function () {
          const status = this.dataset.status || 'Active';
          const badgeClass = status === 'Active' ? 'bg-success' : 'bg-danger';

          document.getElementById('view-roll-no').textContent = toText(this.dataset.rollNo);
          document.getElementById('view-name').textContent = toText(this.dataset.name);
          document.getElementById('view-class').textContent = toText(this.dataset.class);
          document.getElementById('view-email').textContent = toText(this.dataset.email);
          document.getElementById('view-phone').textContent = toText(this.dataset.phone);
          document.getElementById('view-status').innerHTML = '<span class="badge ' + badgeClass + '">' + status + '</span>';
        });
      });

      document.querySelectorAll('.js-edit-student').forEach((btn) => {
        btn.addEventListener('click', function () {
          document.getElementById('edit-student-id').value = this.dataset.id || '';
          document.getElementById('edit-roll-no').value = this.dataset.rollNo || '';
          document.getElementById('edit-name').value = this.dataset.name || '';
          document.getElementById('edit-class').value = this.dataset.class || '';
          document.getElementById('edit-email').value = this.dataset.email || '';
          document.getElementById('edit-phone').value = this.dataset.phone || '';
          document.getElementById('edit-status').value = this.dataset.status || 'Active';
        });
      });

      document.querySelectorAll('.js-delete-student').forEach((btn) => {
        btn.addEventListener('click', function () {
          document.getElementById('delete-student-id').value = this.dataset.id || '';
          document.getElementById('delete-student-name').textContent = this.dataset.name || 'this student';
        });
      });
    });
  </script>
</body>
</html>
