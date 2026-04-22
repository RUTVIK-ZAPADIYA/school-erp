<?php
// Include auth guard
require_once __DIR__ . '/auth.php';

// Resolve student details
$studentContext = student_auth_context();
$student_id = (int) ($studentContext['user_id'] ?? 0);
$success_message = '';
$error_message = '';
$formValues = [
  'category' => '',
    'title' => '',
    'message' => '',
];

// Process ticket form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['title'] = trim((string) ($_POST['title'] ?? ''));
    $formValues['message'] = trim((string) ($_POST['message'] ?? ''));
    $formValues['category'] = trim((string) ($_POST['category'] ?? ''));

  // Insert support ticket
    $sql = "INSERT INTO support_tickets (student_id, title, message, category, status) VALUES (?, ?, ?, ?, 'Open')";
    $stmt = $conn->prepare( $sql);
    if ($stmt) {
        $stmt->bind_param( "isss", $student_id, $formValues['title'], $formValues['message'], $formValues['category']);
        if ($stmt->execute()) {
          // Show success message
            $success_message = 'Your ticket has been submitted successfully! Admin will respond soon.';
            $formValues = [
              'category' => '',
                'title' => '',
                'message' => '',
            ];
        } else {
          // Show error message
            $error_message = 'Error submitting ticket. Please try again.';
        }
        $stmt->close();
    } else {
        $error_message = 'Unable to submit ticket right now. Please try again.';
    }
}

// Fetch student tickets
$tickets = [];
try {
    $sql = "SELECT id, title, category, status, message, admin_reply, created_at, replied_at FROM support_tickets WHERE student_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare( $sql);
    if ($stmt) {
      // Bind student id
        $stmt->bind_param( "i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        // Collect ticket rows
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Tickets query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Admin - Student Portal</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body class="bg-stone-50">
  <?php include 'sidebar.php'; ?>

  <main class="min-h-screen p-4 pt-16 sm:p-6 sm:pt-16 lg:ml-64 lg:p-8 lg:pt-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <span class="material-symbols-outlined text-3xl text-violet-500">support_agent</span>
      <div>
        <h1 class="text-3xl font-bold text-stone-900">Contact Admin</h1>
        <p class="text-sm text-stone-500">Report technical issues or get support</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Contact Form -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6">
          <h2 class="text-lg font-bold text-stone-900 mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">mail</span>
            Send Message to Admin
          </h2>

          <?php if ($success_message): ?>
            <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center gap-3">
              <span class="material-symbols-outlined text-emerald-600">check_circle</span>
              <p class="text-emerald-700 font-medium"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
          <?php endif; ?>

          <form id="contactForm" method="POST" class="space-y-4" novalidate>
            <div>
              <label class="block text-sm font-semibold text-stone-900 mb-2">Category <span class="text-red-600">*</span></label>
              <select name="category" id="category" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500" data-validation="required,select">
                <option value="">-- Select Category --</option>
                <option value="Technical Issue" <?php echo $formValues['category'] === 'Technical Issue' ? 'selected' : ''; ?>>Technical Issue</option>
                <option value="Database Problem" <?php echo $formValues['category'] === 'Database Problem' ? 'selected' : ''; ?>>Database Problem</option>
                <option value="Login Issue" <?php echo $formValues['category'] === 'Login Issue' ? 'selected' : ''; ?>>Login Issue</option>
                <option value="Data Error" <?php echo $formValues['category'] === 'Data Error' ? 'selected' : ''; ?>>Data Error</option>
                <option value="Other" <?php echo $formValues['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
              </select>
              <p id="category_error" class="text-sm text-red-600 mt-1 hidden"></p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-stone-900 mb-2">Subject <span class="text-red-600">*</span></label>
              <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($formValues['title']); ?>" placeholder="Brief description of your issue" class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500" data-validation="required,min,max" data-min="5" data-max="255">
              <p id="title_error" class="text-sm text-red-600 mt-1 hidden"></p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-stone-900 mb-2">Message <span class="text-red-600">*</span></label>
              <textarea name="message" id="message" rows="6" placeholder="Describe your problem in detail..." class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500" data-validation="required,min,max" data-min="10" data-max="5000"><?php echo htmlspecialchars($formValues['message']); ?></textarea>
              <p id="message_error" class="text-sm text-red-600 mt-1 hidden"></p>
            </div>

            <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center gap-2">
              <span class="material-symbols-outlined">send</span>
              Submit Ticket
            </button>
          </form>

          <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm text-blue-700">
              <strong>Note:</strong> Please provide detailed information about your issue so the admin can help you better. We typically respond within 24 hours.
            </p>
          </div>
        </div>
      </div>

      <!-- Stats Panel -->
      <div>
        <div class="bg-white rounded-lg shadow-sm border border-stone-200 p-6 mb-6">
          <h3 class="font-semibold text-stone-900 mb-4">Support Summary</h3>
          <div class="space-y-4">
            <div>
              <p class="text-sm text-stone-500 mb-1">Total Tickets</p>
              <p class="text-2xl font-bold text-violet-600"><?php echo count($tickets); ?></p>
            </div>
            <div>
              <p class="text-sm text-stone-500 mb-1">Resolved</p>
              <p class="text-2xl font-bold text-emerald-600"><?php echo count(array_filter($tickets, fn($t) => $t['status'] === 'Resolved')); ?></p>
            </div>
            <div>
              <p class="text-sm text-stone-500 mb-1">Pending</p>
              <p class="text-2xl font-bold text-amber-600"><?php echo count(array_filter($tickets, fn($t) => $t['status'] === 'Open')); ?></p>
            </div>
          </div>
        </div>

        <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
          <p class="text-sm text-blue-700">
            <strong>📞 Quick Help:</strong> For urgent issues, contact your school office directly.
          </p>
        </div>
      </div>
    </div>

    <!-- Ticket History -->
    <div class="mt-12">
      <div class="bg-white rounded-lg shadow-sm border border-stone-200">
        <div class="p-6 border-b border-stone-200">
          <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
            <span class="material-symbols-outlined">history</span>
            Your Support Tickets
          </h2>
        </div>

        <?php if (count($tickets) > 0): ?>
          <div class="divide-y divide-stone-200">
            <?php foreach ($tickets as $ticket): ?>
              <div class="p-6 hover:bg-stone-50 transition">
                <div class="flex items-start justify-between mb-3">
                  <div>
                    <h3 class="font-semibold text-stone-900"><?php echo htmlspecialchars($ticket['title']); ?></h3>
                    <p class="text-sm text-stone-500 mt-1">
                      <span class="material-symbols-outlined text-xs align-text-bottom">schedule</span>
                      <?php echo date('d M Y H:i', strtotime($ticket['created_at'])); ?>
                    </p>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $ticket['status'] === 'Resolved' ? 'bg-emerald-100 text-emerald-700' : ($ticket['status'] === 'Open' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'); ?>">
                      <?php echo htmlspecialchars($ticket['status']); ?>
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-stone-100 text-stone-700">
                      <?php echo htmlspecialchars($ticket['category']); ?>
                    </span>
                  </div>
                </div>

                <p class="text-sm text-stone-700 mb-4"><?php echo htmlspecialchars($ticket['message']); ?></p>

                <?php if ($ticket['admin_reply']): ?>
                  <div class="mt-4 p-4 bg-stone-50 rounded-lg border-l-4 border-emerald-500">
                    <p class="text-xs font-semibold text-stone-600 mb-2">
                      <span class="material-symbols-outlined text-xs align-text-bottom">admin_panel_settings</span>
                      Admin Reply
                    </p>
                    <p class="text-sm text-stone-700"><?php echo htmlspecialchars($ticket['admin_reply']); ?></p>
                    <p class="text-xs text-stone-500 mt-2">
                      Replied: <?php echo date('d M Y H:i', strtotime($ticket['replied_at'])); ?>
                    </p>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="p-12 text-center">
            <span class="material-symbols-outlined text-5xl text-stone-300 inline-block mb-3">mail_outlined</span>
            <p class="text-stone-500">No tickets submitted yet</p>
            <p class="text-sm text-stone-400 mt-1">Submit a ticket above if you need help</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script src="../js/jquery.js"></script>
  <script src="../js/validate.js"></script>
</body>
</html>
