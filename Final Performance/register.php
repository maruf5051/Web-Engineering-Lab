<?php

$conn = new mysqli("localhost", "root", "", "comp207");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

function getSlots($conn)
{
  $slots = [];
  $q = $conn->query("SELECT slot_id, label, capacity FROM slots ORDER BY slot_id");
  while ($row = $q->fetch_assoc()) $slots[$row['slot_id']] = $row;
  $q->free();
  return $slots;
}
function getCounts($conn)
{
  $counts = [];
  $q = $conn->query("SELECT slot_id, COUNT(*) AS c FROM registrations GROUP BY slot_id");
  while ($r = $q->fetch_assoc()) $counts[$r['slot_id']] = (int)$r['c'];
  $q->free();
  return $counts;
}
function remainingMap($conn)
{
  $slots = getSlots($conn);
  $counts = getCounts($conn);
  $out = [];
  foreach ($slots as $id => $s) {
    $used = $counts[$id] ?? 0;
    $out[$id] = max(0, (int)$s['capacity'] - $used);
  }
  return $out;
}

/** JSON API for seat counts **/
if (isset($_GET['action']) && $_GET['action'] === 'slots') {
  header('Content-Type: application/json');
  $slots = getSlots($conn);
  $rem   = remainingMap($conn);
  $data = [];
  foreach ($slots as $id => $s) {
    $data[$id] = [
      'label'     => $s['label'],
      'capacity'  => (int)$s['capacity'],
      'remaining' => $rem[$id] ?? (int)$s['capacity']
    ];
  }
  echo json_encode(['ok' => true, 'slots' => $data]);
  exit;
}

/** Handle submission **/
$message = '';
$alert   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['__move_confirm'])) {
  $firstname = trim($_POST['firstname'] ?? '');
  $lastname  = trim($_POST['lastname']  ?? '');
  $sid       = trim($_POST['sid']       ?? '');
  $email     = trim($_POST['email']     ?? '');
  $slot      = trim($_POST['slot']      ?? '');

  // Server-side validation
  $nameRegex = '/^[A-Za-z\s\-]+$/u';
  $sidRegex  = '/^\d{3}-\d{2}-\d{4}$/';
  $emailRegex = '/^[A-Za-z0-9._%+\-]+.cse\@diu\.edu\.bd$/i'; // assumption

  if ($firstname === '' || $lastname === '' || $sid === '' || $email === '' || $slot === '') {
    $alert = 'error';
    $message = 'All fields are required.';
  } elseif (!preg_match($nameRegex, $firstname) || !preg_match($nameRegex, $lastname)) {
    $alert = 'error';
    $message = 'Names can only contain letters, spaces, and hyphens.';
  } elseif (!preg_match($sidRegex, $sid)) {
    $alert = 'error';
    $message = 'SID must match the format xxx-xx-xxxx (e.g., 221-15-5051).';
  } elseif (!preg_match($emailRegex, $email)) {
    $alert = 'error';
    $message = 'Email must be a .cse@diu.edu.bd address.';
  } else {
    // Ensure slot exists
    $slotStmt = $conn->prepare("SELECT capacity, label FROM slots WHERE slot_id = ?");
    $slotStmt->bind_param('s', $slot);
    $slotStmt->execute();
    $slotStmt->store_result();
    if ($slotStmt->num_rows === 0) {
      $alert = 'error';
      $message = 'Selected slot does not exist.';
      $slotStmt->close();
    } else {
      $slotStmt->bind_result($capacity, $slotLabel);
      $slotStmt->fetch();
      $slotStmt->close();

      // Check if this SID already exists
      $chk = $conn->prepare("SELECT slot_id FROM registrations WHERE sid = ?");
      $chk->bind_param('s', $sid);
      $chk->execute();
      $res = $chk->get_result();
      $exist = $res->fetch_assoc();
      $res->free();
      $chk->close();

      if ($exist && $exist['slot_id'] === $slot) {
        $alert = 'info';
        $message = "You are already registered for <strong>{$slotLabel}</strong>.";
      } elseif ($exist && $exist['slot_id'] !== $slot) {
        // Ask for confirmation to move
        $prevSlotId = $exist['slot_id'];
        $prev = $conn->prepare("SELECT label FROM slots WHERE slot_id = ?");
        $prev->bind_param('s', $prevSlotId);
        $prev->execute();
        $resPrev = $prev->get_result();
        $prevLabel = $resPrev->fetch_assoc()['label'] ?? $prevSlotId;
        $resPrev->free();
        $prev->close();

        $pendingMove = [
          'firstname' => $firstname,
          'lastname' => $lastname,
          'sid' => $sid,
          'email' => $email,
          'from' => $prevSlotId,
          'fromLabel' => $prevLabel,
          'to' => $slot,
          'toLabel' => $slotLabel
        ];
      } else {
        // New registration: check capacity remains
        $cnt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE slot_id = ?");
        $cnt->bind_param('s', $slot);
        $cnt->execute();
        $cnt->bind_result($used);
        $cnt->fetch();
        $cnt->close();

        if ($used >= $capacity) {
          $alert = 'error';
          $message = "Sorry, <strong>{$slotLabel}</strong> is fully booked.";
        } else {
          $ins = $conn->prepare("INSERT INTO registrations (firstname, lastname, sid, email, slot_id) VALUES (?,?,?,?,?)");
          $ins->bind_param('sssss', $firstname, $lastname, $sid, $email, $slot);
          $ins->execute();
          $ins->close();
          $alert = 'success';
          $message = "Registration successful for <strong>{$slotLabel}</strong>.";
        }
      }
    }
  }
}

// Handle confirmed move
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__move_confirm'] ?? '') === '1') {
  $firstname = trim($_POST['firstname']);
  $lastname  = trim($_POST['lastname']);
  $sid       = trim($_POST['sid']);
  $email     = trim($_POST['email']);
  $to        = trim($_POST['to']);

  $slotStmt = $conn->prepare("SELECT capacity, label FROM slots WHERE slot_id = ?");
  $slotStmt->bind_param('s', $to);
  $slotStmt->execute();
  $slotStmt->bind_result($capacity, $slotLabel);
  $slotStmt->fetch();
  $slotStmt->close();

  $cnt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE slot_id = ?");
  $cnt->bind_param('s', $to);
  $cnt->execute();
  $cnt->bind_result($used);
  $cnt->fetch();
  $cnt->close();

  if ($used >= $capacity) {
    $alert = 'error';
    $message = "Cannot move: <strong>{$slotLabel}</strong> just became full.";
  } else {
    $upd = $conn->prepare("UPDATE registrations SET firstname=?, lastname=?, email=?, slot_id=? WHERE sid=?");
    $upd->bind_param('sssss', $firstname, $lastname, $email, $to, $sid);
    $upd->execute();
    $upd->close();
    $alert = 'success';
    $message = "Registration moved successfully to <strong>{$slotLabel}</strong>.";
  }
}

// Fetch slots + remaining for rendering
$slotsData = getSlots($conn);
$remaining = remainingMap($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>COMP207 - Student Registration</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container">
    <h1>COMP207 - Register here for a practical slot</h1>

    <p class="warning">Register only if you know what you are doing.</p>

    <ul class="instructions">
      <li>Please enter all information and select your desired day. Please enter a correct 'SID' number!</li>
      <li>Please check the number of available seats before submitting.</li>
      <li>Register only to one slot.</li>
    </ul>

    <?php if (!empty($message)): ?>
      <div class="alert <?= $alert ?>"><?= $message ?></div>
    <?php endif; ?>

    <form id="regForm" action="register.php" method="POST" novalidate>
      <table class="form-table">
        <tr>
          <th>Firstname</th>
          <th>Lastname</th>
          <th>SID</th>
          <th>Email Address</th>
        </tr>
        <tr>
          <td><input type="text" name="firstname" required pattern="[A-Za-z\s\-]+" title="Letters, spaces, and hyphens only"></td>
          <td><input type="text" name="lastname" required pattern="[A-Za-z\s\-]+" title="Letters, spaces, and hyphens only"></td>
          <td><input type="text" name="sid" required pattern="\d{3}-\d{2}-\d{4}" title="Format: 3 digits, hyphen, 2 digits, hyphen, 4 digits"></td>
          <td><input type="email" name="email" required pattern="^[A-Za-z0-9._%+\-]+.cse\@diu\.edu\.bd$" title="Must be a cse.diu.edu.bd address"></td>
        </tr>
      </table>

      <label for="slot" class="slot-label">Select the practical slot:</label><br>
      <select name="slot" id="slot" size="4" required>
        <?php foreach ($slotsData as $id => $s):
          $rem = $remaining[$id] ?? $s['capacity'];
          $disabled = $rem <= 0 ? 'disabled' : '';
          $remText = $rem <= 0 ? '— FULL' : "— {$rem} seats remaining";
        ?>
          <option value="<?= htmlspecialchars($id) ?>" <?= $disabled ?>>
            <?= htmlspecialchars($s['label']) ?> <?= $remText ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="buttons">
        <input type="submit" value="Register">
        <input type="reset" value="Clear">
      </div>
    </form>

    <?php if (isset($pendingMove)): ?>
      <form method="POST" action="register.php" style="margin-top:1rem">
        <input type="hidden" name="__move_confirm" value="1">
        <input type="hidden" name="firstname" value="<?= htmlspecialchars($pendingMove['firstname']) ?>">
        <input type="hidden" name="lastname" value="<?= htmlspecialchars($pendingMove['lastname'])  ?>">
        <input type="hidden" name="sid" value="<?= htmlspecialchars($pendingMove['sid'])       ?>">
        <input type="hidden" name="email" value="<?= htmlspecialchars($pendingMove['email'])     ?>">
        <input type="hidden" name="to" value="<?= htmlspecialchars($pendingMove['to'])        ?>">

        <div class="alert info">
          You are already registered for <strong><?= htmlspecialchars($pendingMove['fromLabel']) ?></strong>.<br>
          Do you want to change to <strong><?= htmlspecialchars($pendingMove['toLabel']) ?></strong>?
        </div>
        <div class="buttons">
          <button type="submit">Yes, move me</button>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <script>
    const form = document.getElementById('regForm');
    const slotSel = document.getElementById('slot');

    async function refreshSeats() {
      try {
        const res = await fetch('register.php?action=slots', {
          cache: 'no-store'
        });
        const data = await res.json();
        if (!data.ok) return;
        const map = data.slots;

        Array.from(slotSel.options).forEach(opt => {
          const id = opt.value;
          const s = map[id];
          if (!s) return;
          const rem = s.remaining;
          opt.textContent = `${s.label} — ${rem > 0 ? rem + ' seats remaining' : 'FULL'}`;
          opt.disabled = rem <= 0;
        });
      } catch (e) {}
    }
    refreshSeats();
    setInterval(refreshSeats, 10000);

    form.addEventListener('submit', async (e) => {
      const email = form.email.value.trim();
      const sid = form.sid.value.trim();
      const nameRe = /^[A-Za-z\s\-]+$/;
      const sidRe = /^\d{3}-\d{2}-\d{4}$/;
      const emailRe = /^[A-Za-z0-9_%+\-]+.cse\@diu\.edu\.bd$/i;

      if (!nameRe.test(form.firstname.value) || !nameRe.test(form.lastname.value)) {
        alert('Names can only contain letters, spaces, and hyphens.');
        e.preventDefault();
        return;
      }
      if (!sidRe.test(sid)) {
        alert('SID must match the format xxx-xx-xxxx (e.g., 221-15-5051).');
        e.preventDefault();
        return;
      }
      if (!emailRe.test(email)) {
        alert('Email must be a .cse@diu.edu.bd address.');
        e.preventDefault();
        return;
      }

      try {
        const res = await fetch('register.php?action=slots', {
          cache: 'no-store'
        });
        const data = await res.json();
        const chosen = form.slot.value;
        if (data?.slots?.[chosen]?.remaining === 0) {
          alert('Sorry, that slot just became full. Please choose another.');
          e.preventDefault();
          await refreshSeats();
        }
      } catch (_) {}
    });
  </script>
</body>

</html>