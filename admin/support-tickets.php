<?php
// Admin page for handling support tickets.
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
  header("Location: ../login.php");
  exit();
}

include '../includes/db_connect.php';

$admin_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle admin replies and resolve ticket status.
// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reply') {
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $admin_reply = trim($_POST['admin_reply'] ?? '');

        if ($ticket_id > 0 && !empty($admin_reply) && strlen($admin_reply) >= 10) {
            $sql = "UPDATE support_tickets SET admin_reply = ?, status = 'Resolved', replied_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare( $sql);
            if ($stmt) {
                $stmt->bind_param( "si", $admin_reply, $ticket_id);
                if ($stmt->execute()) {
                    $success_message = 'Reply sent successfully!';
                } else {
                    $error_message = 'Error sending reply. Please try again.';
                }
                $stmt->close();
            }
        }
    }
}

    // Load all tickets with student metadata for admin review.
// Get all support tickets
$tickets = [];
try {
    $sql = "SELECT
            st.id,
            st.student_id,
            st.title,
            st.message,
            st.category,
            st.status,
            st.admin_reply,
            st.created_at,
            st.replied_at,
            u.name as student_name,
            u.email as student_email
        FROM support_tickets st
        LEFT JOIN users u ON st.student_id = u.id
        ORDER BY st.status ASC, st.created_at DESC";

    $result = $conn->query( $sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Tickets query error: " . $e->getMessage());
}

// Compute ticket counts displayed in dashboard cards.
// Get statistics
$open_count = count(array_filter($tickets, fn($t) => $t['status'] === 'Open'));
$resolved_count = count(array_filter($tickets, fn($t) => $t['status'] === 'Resolved'));
$total_count = count($tickets);
?>
<!-- Render support ticket cards, reply forms, and status summaries. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support Tickets - Admin Panel</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/validate.js"></script>
  <!-- jQuery Validation Plugin -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .error {
      color: #dc2626 !important;
      font-size: 0.875rem !important;
      margin-top: 0.25rem !important;
      display: block !important;
    }
    textarea.error {
      border-color: #dc2626 !important;
      background-color: #fee2e2 !important;
    }
  </style>
</head>
<body class="bg-stone-50">
  <?php include '../admin/sidebar.php'; ?>

  <main class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-blue-500">support_agent</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Support Tickets</h1>
        <p class="text-sm text-stone-500">Manage student support requests</p>
      </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Total Tickets</p>
        <p class="text-3xl font-bold text-blue-600"><?php echo $total_count; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Open Tickets</p>
        <p class="text-3xl font-bold text-amber-600"><?php echo $open_count; ?></p>
      </div>
      <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-200">
        <p class="text-sm text-stone-500 mb-2">Resolved</p>
        <p class="text-3xl font-bold text-emerald-600"><?php echo $resolved_count; ?></p>
      </div>
    </div>

    <?php if ($success_message): ?>
      <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center gap-3">
        <span class="material-symbols-outlined text-emerald-600">check_circle</span>
        <p class="text-emerald-700 font-medium"><?php echo htmlspecialchars($success_message); ?></p>
      </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
      <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 flex items-center gap-3">
        <span class="material-symbols-outlined text-red-600">error</span>
        <p class="text-red-700 font-medium"><?php echo htmlspecialchars($error_message); ?></p>
      </div>
    <?php endif; ?>

    <!-- Tickets List -->
    <div class="space-y-6">
      <?php if (count($tickets) > 0): ?>
        <?php foreach ($tickets as $ticket): ?>
          <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 <?php echo $ticket['status'] === 'Resolved' ? 'opacity-75' : ''; ?>">
            <!-- Ticket Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-lg font-bold text-stone-900"><?php echo htmlspecialchars($ticket['title']); ?></h3>
                  <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $ticket['status'] === 'Resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                    <?php echo htmlspecialchars($ticket['status']); ?>
                  </span>
                  <span class="px-3 py-1 rounded-full text-xs font-semibold bg-stone-100 text-stone-700">
                    <?php echo htmlspecialchars($ticket['category']); ?>
                  </span>
                </div>
                <p class="text-sm text-stone-500">
                  From: <strong><?php echo htmlspecialchars($ticket['student_name'] ?? 'Unknown'); ?></strong>
                  (<?php echo htmlspecialchars($ticket['student_email'] ?? 'No email'); ?>)
                </p>
                <p class="text-xs text-stone-400 mt-1">
                  <span class="material-symbols-outlined text-xs align-text-bottom">schedule</span>
                  Submitted: <?php echo date('d M Y H:i', strtotime($ticket['created_at'])); ?>
                </p>
              </div>
            </div>

            <!-- Student Message -->
            <div class="bg-stone-50 rounded-lg p-4 mb-4 border border-stone-200">
              <p class="text-sm font-semibold text-stone-900 mb-2">Student Message:</p>
              <p class="text-sm text-stone-700"><?php echo htmlspecialchars($ticket['message']); ?></p>
            </div>

            <!-- Admin Reply (if exists) -->
            <?php if ($ticket['admin_reply']): ?>
              <div class="bg-emerald-50 rounded-lg p-4 mb-4 border border-emerald-200">
                <p class="text-sm font-semibold text-emerald-900 mb-2">Admin Reply:</p>
                <p class="text-sm text-emerald-700"><?php echo htmlspecialchars($ticket['admin_reply']); ?></p>
                <p class="text-xs text-emerald-600 mt-2">
                  <span class="material-symbols-outlined text-xs align-text-bottom">schedule</span>
                  Replied: <?php echo date('d M Y H:i', strtotime($ticket['replied_at'])); ?>
                </p>
              </div>
            <?php else: ?>
              <!-- Reply Form -->
              <form method="POST" class="replyForm" data-ticket="<?php echo $ticket['id']; ? novalidate>" novalidate>
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">

                <div class="mb-3">
                  <label class="block text-sm font-semibold text-stone-900 mb-2">Send Reply <span class="text-red-600">*</span></label>
                  <textarea
                    name="admin_reply"
                    required
                    minlength="10"
                    maxlength="5000"
                    rows="4"
                    placeholder="Type your response here..."
                    class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  ></textarea>
                </div>

                <button
                  type="submit"
                  class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                >
                  <span class="material-symbols-outlined">send</span>
                  Send Reply & Mark Resolved
                </button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-12 text-center">
          <span class="material-symbols-outlined text-5xl text-stone-300 inline-block mb-3">mail_outline</span>
          <p class="text-stone-500 font-medium">No support tickets yet</p>
          <p class="text-sm text-stone-400 mt-1">Students haven't submitted any support requests</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    $(document).ready(function() {
      // Initialize jQuery Validation for reply forms
      $('.replyForm').each(function() {
        $(this).validate({
          rules: {
            admin_reply: {
              required: true,
              minlength: 10,
              maxlength: 5000
            }
          },
          messages: {
            admin_reply: {
              required: "Reply message is required",
              minlength: "Reply must be at least 10 characters",
              maxlength: "Reply cannot exceed 5000 characters"
            }
          },
          errorElement: 'span',
          errorClass: 'error',
          highlight: function(element, errorClass, validClass) {
            $(element).addClass('border-red-600 bg-red-50');
          },
          unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('border-red-600 bg-red-50');
          },
          submitHandler: function(form) {
            form.submit();
          }
        });
      });
    });
  </script>
</body>
</html>
