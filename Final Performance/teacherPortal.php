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
  return $slots;
}
function remaining($conn, $slot_id)
{
  $capStmt = $conn->prepare("SELECT capacity FROM slots WHERE slot_id=?");
  $capStmt->bind_param('s', $slot_id);
  $capStmt->execute();
  $capacity = 0;
  $capStmt->bind_result($capacity);
  if (!$capStmt->fetch()) return null;
  $capStmt->close();

  $cntStmt = $conn->prepare("SELECT COUNT(*) FROM registrations WHERE slot_id=?");
  $cntStmt->bind_param('s', $slot_id);
  $cntStmt->execute();
  $used = 0;
  $cntStmt->bind_result($used);
  $cntStmt->fetch();
  $cntStmt->close();
  return max(0, $capacity - $used);
}

$slots = getSlots($conn);
$chosen = $_GET['slot'] ?? '';
$list = [];
$slotLabel = '';
$remainingSeats = null;

if ($chosen && isset($slots[$chosen])) {
  $slotLabel = $slots[$chosen]['label'];
  $remainingSeats = remaining($conn, $chosen);
  $stmt = $conn->prepare("SELECT firstname, lastname, sid, email FROM registrations WHERE slot_id=? ORDER BY lastname, firstname");
  $stmt->bind_param('s', $chosen);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $list[] = $row;
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>COMP207 - Teacher Panel</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container">
    <h1>COMP207 - Teacher Panel</h1>

    <p class="warning">View registered students for each practical slot.</p>

    <form action="teacherPortal.php" method="GET">
      <label for="slot" class="slot-label">Select the practical slot:</label><br>
      <select name="slot" id="slot" size="4" required>
        <?php foreach ($slots as $id => $s): ?>
          <option value="<?= htmlspecialchars($id) ?>" <?= $chosen === $id ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="buttons">
        <input type="submit" value="View Students">
        <input type="reset" value="Clear" onclick="location.href='teacherPortal.php'">
      </div>
    </form>

    <div class="results">
      <?php if ($chosen && isset($slots[$chosen])): ?>
        <h2><?= htmlspecialchars($slotLabel) ?></h2>
        <p class="muted">Remaining seats: <strong><?= $remainingSeats ?></strong></p>

        <?php if (count($list) > 0): ?>
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Firstname</th>
                <th>Lastname</th>
                <th>SID</th>
                <th>Email</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1;
              foreach ($list as $row): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($row['firstname']) ?></td>
                  <td><?= htmlspecialchars($row['lastname']) ?></td>
                  <td><?= htmlspecialchars($row['sid']) ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">No students registered for this slot yet.</p>
        <?php endif; ?>
      <?php else: ?>
        <p class="muted">After selecting a slot, the list of registered students will appear here.</p>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>