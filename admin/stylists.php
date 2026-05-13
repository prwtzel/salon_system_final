<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   CREATE TABLES IF NEEDED
========================= */
$conn->query("
    CREATE TABLE IF NOT EXISTS stylist_timeslots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stylist_id INT NOT NULL,
        day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status ENUM('available','unavailable') DEFAULT 'available',
        FOREIGN KEY (stylist_id) REFERENCES stylists(id) ON DELETE CASCADE
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS stylist_timeslot_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timeslot_id INT NOT NULL,
        available_date DATE NOT NULL,
        FOREIGN KEY (timeslot_id) REFERENCES stylist_timeslots(id) ON DELETE CASCADE
    )
");

/* =========================
   ADD STYLIST
========================= */
if (isset($_POST['add'])) {
    $name    = $conn->real_escape_string($_POST['name']);
    $service = !empty($_POST['service']) ? $_POST['service'] : NULL;
    $conn->query("
        INSERT INTO stylists (name, service_id)
        VALUES ('$name', " . ($service ? "'$service'" : "NULL") . ")
    ");
    header("Location: stylists.php");
    exit();
}

/* =========================
   DELETE STYLIST
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM stylists WHERE id='$id'");
    header("Location: stylists.php");
    exit();
}

/* =========================
   UPDATE STYLIST
========================= */
if (isset($_POST['update'])) {
    $id      = intval($_POST['id']);
    $name    = $conn->real_escape_string($_POST['name']);
    $service = !empty($_POST['service']) ? $_POST['service'] : NULL;
    $conn->query("
        UPDATE stylists
        SET name='$name', service_id=" . ($service ? "'$service'" : "NULL") . "
        WHERE id='$id'
    ");
    header("Location: stylists.php");
    exit();
}

/* =========================
   LOAD EDIT STYLIST
========================= */
$editData = null;
if (isset($_GET['edit'])) {
    $id     = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM stylists WHERE id='$id'");
    $editData = $result->fetch_assoc();
}

/* =========================
   ADD TIME SLOT (AJAX)
========================= */
if (isset($_POST['add_slot'])) {
    $stylist_id = intval($_POST['stylist_id']);
    $day        = $conn->real_escape_string($_POST['day_of_week']);
    $start      = $conn->real_escape_string($_POST['start_time']);
    $end        = $conn->real_escape_string($_POST['end_time']);
    $status     = $_POST['status'] === 'unavailable' ? 'unavailable' : 'available';

    if ($end <= $start) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
    } else {
        $conn->query("
            INSERT INTO stylist_timeslots (stylist_id, day_of_week, start_time, end_time, status)
            VALUES ('$stylist_id','$day','$start','$end','$status')
        ");
        echo json_encode(['success' => true, 'insert_id' => $conn->insert_id]);
    }
    exit();
}

/* =========================
   UPDATE TIME SLOT (AJAX)
========================= */
if (isset($_POST['update_slot'])) {
    $id         = intval($_POST['id']);
    $stylist_id = intval($_POST['stylist_id']);
    $day        = $conn->real_escape_string($_POST['day_of_week']);
    $start      = $conn->real_escape_string($_POST['start_time']);
    $end        = $conn->real_escape_string($_POST['end_time']);
    $status     = $_POST['status'] === 'unavailable' ? 'unavailable' : 'available';

    if ($end <= $start) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
    } else {
        $conn->query("
            UPDATE stylist_timeslots
            SET day_of_week='$day', start_time='$start', end_time='$end', status='$status'
            WHERE id='$id'
        ");
        echo json_encode(['success' => true]);
    }
    exit();
}

/* =========================
   DELETE TIME SLOT (AJAX)
========================= */
if (isset($_POST['delete_slot'])) {
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM stylist_timeslots WHERE id='$id'");
    echo json_encode(['success' => true]);
    exit();
}

/* =========================
   TOGGLE TIME SLOT STATUS (AJAX)
========================= */
if (isset($_POST['toggle_slot'])) {
    $id = intval($_POST['id']);
    $conn->query("
        UPDATE stylist_timeslots
        SET status = IF(status='available','unavailable','available')
        WHERE id='$id'
    ");
    $r   = $conn->query("SELECT status FROM stylist_timeslots WHERE id='$id'");
    $row = $r->fetch_assoc();
    echo json_encode(['success' => true, 'status' => $row['status']]);
    exit();
}

/* =========================
   GET SLOTS FOR STYLIST (AJAX)
========================= */
if (isset($_GET['get_slots'])) {
    $stylist_id = intval($_GET['stylist_id']);
    $result = $conn->query("
        SELECT * FROM stylist_timeslots
        WHERE stylist_id='$stylist_id'
        ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time
    ");
    $slots = [];
    while ($row = $result->fetch_assoc()) {
        // get specific dates for this slot
        $sid   = $row['id'];
        $dres  = $conn->query("SELECT available_date FROM stylist_timeslot_dates WHERE timeslot_id='$sid' ORDER BY available_date ASC");
        $dates = [];
        while ($dr = $dres->fetch_assoc()) {
            $dates[] = $dr['available_date'];
        }
        $row['specific_dates'] = $dates;
        $slots[] = $row;
    }
    echo json_encode($slots);
    exit();
}

/* =========================
   ADD SPECIFIC DATE TO SLOT (AJAX)
========================= */
if (isset($_POST['add_slot_date'])) {
    $timeslot_id = intval($_POST['timeslot_id']);
    $date        = $conn->real_escape_string($_POST['available_date']);

    // prevent duplicates
    $check = $conn->query("SELECT id FROM stylist_timeslot_dates WHERE timeslot_id='$timeslot_id' AND available_date='$date'");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'That date is already added.']);
    } else {
        $conn->query("INSERT INTO stylist_timeslot_dates (timeslot_id, available_date) VALUES ('$timeslot_id','$date')");
        echo json_encode(['success' => true]);
    }
    exit();
}

/* =========================
   REMOVE SPECIFIC DATE FROM SLOT (AJAX)
========================= */
if (isset($_POST['remove_slot_date'])) {
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM stylist_timeslot_dates WHERE id='$id'");
    echo json_encode(['success' => true]);
    exit();
}

/* =========================
   GET DATES FOR SLOT (AJAX)
========================= */
if (isset($_GET['get_slot_dates'])) {
    $timeslot_id = intval($_GET['timeslot_id']);
    $result = $conn->query("SELECT * FROM stylist_timeslot_dates WHERE timeslot_id='$timeslot_id' ORDER BY available_date ASC");
    $dates  = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row;
    }
    echo json_encode($dates);
    exit();
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Stylists</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --gold:     #c9a84c;
  --gold-lt:  #e8cc80;
  --dark:     #0d0c0a;
  --cream:    #fdf8f0;
  --radius:   14px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Jost', sans-serif;
  background: url('../assets/img/bg.jpg') no-repeat center center fixed;
  background-size: cover;
  min-height: 100vh;
}

.overlay {
  min-height: 100vh;
  background: rgba(10,9,7,0.70);
  padding: 36px 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
}
.page-title {
  font-family: 'Cormorant Garamond', serif;
  color: var(--gold);
  font-size: 2rem;
  letter-spacing: .5px;
  text-shadow: 0 2px 14px rgba(0,0,0,.5);
}
.btn-dash {
  background: transparent;
  border: 1px solid var(--gold);
  color: var(--gold);
  font-size: .82rem;
  padding: 6px 20px;
  border-radius: 30px;
  text-decoration: none;
  transition: all .2s;
  font-family: 'Jost', sans-serif;
}
.btn-dash:hover { background: var(--gold); color: var(--dark); }

.card-custom {
  background: rgba(255,255,255,0.96);
  border-radius: var(--radius);
  padding: 26px 28px;
  margin-bottom: 22px;
  box-shadow: 0 8px 36px rgba(0,0,0,.28);
}
.card-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.15rem;
  color: #222;
  border-bottom: 2px solid var(--gold);
  padding-bottom: 8px;
  margin-bottom: 20px;
}

.form-control, .form-select {
  font-family: 'Jost', sans-serif;
  font-size: .9rem;
  border-radius: 8px;
  border: 1.5px solid #ddd;
  padding: 9px 13px;
  transition: border .2s;
}
.form-control:focus, .form-select:focus {
  border-color: var(--gold);
  box-shadow: none;
}
.btn-gold {
  background: var(--gold);
  color: var(--dark);
  border: none;
  padding: 9px 24px;
  border-radius: 8px;
  font-family: 'Jost', sans-serif;
  font-weight: 600;
  font-size: .88rem;
  cursor: pointer;
  transition: background .2s;
}
.btn-gold:hover { background: var(--gold-lt); }

table { width: 100%; border-collapse: collapse; font-size: .88rem; }
thead tr { background: var(--dark); color: var(--gold); }
thead th { padding: 11px 14px; font-weight: 500; letter-spacing: .4px; text-align: center; font-family: 'Jost', sans-serif; }
tbody tr { border-bottom: 1px solid #eee; transition: background .15s; }
tbody tr:hover { background: #fdf8ee; }
tbody td { padding: 10px 14px; text-align: center; color: #333; }

.btn-act {
  padding: 4px 13px;
  border-radius: 6px;
  font-size: .78rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  transition: opacity .2s;
  margin: 2px;
  font-family: 'Jost', sans-serif;
}
.btn-act:hover { opacity: .82; }
.btn-edit   { background: #fef3c7; color: #92400e; }
.btn-delete { background: #fee2e2; color: #991b1b; }
.btn-slots  { background: #e0f2fe; color: #0369a1; }

/* ── MAIN SLOTS MODAL ── */
.modal-backdrop-custom {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(8,7,5,0.78);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(3px);
}
.modal-backdrop-custom.open { display: flex; }

.modal-box {
  background: var(--cream);
  border-radius: 18px;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,.55);
  animation: slideUp .25s ease;
}

@keyframes slideUp {
  from { opacity:0; transform: translateY(30px); }
  to   { opacity:1; transform: translateY(0); }
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 22px 26px 16px;
  border-bottom: 2px solid var(--gold);
  position: sticky;
  top: 0;
  background: var(--cream);
  z-index: 2;
  border-radius: 18px 18px 0 0;
}
.modal-head-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.35rem;
  color: #1a1a1a;
}
.modal-close {
  background: none;
  border: none;
  font-size: 1.4rem;
  cursor: pointer;
  color: #888;
  line-height: 1;
  transition: color .2s;
}
.modal-close:hover { color: #333; }

.modal-body { padding: 22px 26px; }

.slot-form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 14px;
}
.slot-form-grid label {
  font-size: .78rem;
  font-weight: 600;
  color: #666;
  margin-bottom: 4px;
  display: block;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.slot-form-grid input,
.slot-form-grid select {
  width: 100%;
  padding: 8px 11px;
  border-radius: 8px;
  border: 1.5px solid #ddd;
  font-family: 'Jost', sans-serif;
  font-size: .88rem;
  background: #fff;
  transition: border .2s;
}
.slot-form-grid input:focus,
.slot-form-grid select:focus { outline: none; border-color: var(--gold); }

.slot-error {
  background: #fde8e8;
  color: #b91c1c;
  border-radius: 8px;
  padding: 8px 14px;
  font-size: .84rem;
  margin-bottom: 12px;
  display: none;
}
.slot-success {
  background: #d1fae5;
  color: #065f46;
  border-radius: 8px;
  padding: 8px 14px;
  font-size: .84rem;
  margin-bottom: 12px;
  display: none;
}

.slot-table-wrap { margin-top: 20px; }
.slot-section-label {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1rem;
  color: #333;
  border-bottom: 1.5px solid #e8d8a0;
  padding-bottom: 6px;
  margin-bottom: 12px;
}

.slot-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.slot-table thead tr { background: #1a1a1a; color: var(--gold); }
.slot-table thead th { padding: 9px 12px; text-align: center; font-weight: 500; }
.slot-table tbody tr { border-bottom: 1px solid #ece8df; transition: background .12s; }
.slot-table tbody tr:hover { background: #fdf5e4; }
.slot-table tbody td { padding: 8px 12px; text-align: center; color: #333; }
.slot-table .day-header td {
  background: #f5edd8;
  color: #7a5c1e;
  font-size: .76rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .7px;
  text-align: left;
  padding: 6px 12px;
}

.badge-av { background: #d1fae5; color: #065f46; padding: 3px 11px; border-radius: 20px; font-size: .75rem; font-weight: 600; }
.badge-un { background: #fee2e2; color: #991b1b; padding: 3px 11px; border-radius: 20px; font-size: .75rem; font-weight: 600; }

.btn-sm-act {
  padding: 3px 10px;
  border-radius: 5px;
  font-size: .74rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: opacity .2s;
  margin: 1px;
  font-family: 'Jost', sans-serif;
}
.btn-sm-act:hover { opacity: .82; }
.s-edit   { background: #fef3c7; color: #92400e; }
.s-delete { background: #fee2e2; color: #991b1b; }
.s-tog-av { background: #fee2e2; color: #991b1b; }
.s-tog-un { background: #d1fae5; color: #065f46; }
.s-dates  { background: #ede9fe; color: #5b21b6; }

.slot-empty { color: #bbb; font-style: italic; text-align: center; padding: 20px; font-size: .88rem; }
.loading-slots { text-align: center; padding: 30px; color: #999; font-size: .9rem; }

/* dates badge in slot table */
.dates-badge {
  display: inline-block;
  background: #ede9fe;
  color: #5b21b6;
  border-radius: 12px;
  padding: 2px 9px;
  font-size: .72rem;
  font-weight: 600;
}
.dates-badge.any {
  background: #e0f2fe;
  color: #0369a1;
}

/* ── DATES SUB-MODAL ── */
.sub-modal-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(8,7,5,0.60);
  z-index: 1100;
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(2px);
}
.sub-modal-backdrop.open { display: flex; }

.sub-modal-box {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 460px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,.45);
  animation: slideUp .2s ease;
}
.sub-modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 22px 14px;
  border-bottom: 2px solid var(--gold);
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 2;
  border-radius: 16px 16px 0 0;
}
.sub-modal-head-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.1rem;
  color: #1a1a1a;
}
.sub-modal-body { padding: 18px 22px; }

/* date add row */
.date-add-row {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 16px;
}
.date-add-row input[type="date"] {
  flex: 1;
  padding: 8px 11px;
  border-radius: 8px;
  border: 1.5px solid #ddd;
  font-family: 'Jost', sans-serif;
  font-size: .88rem;
  transition: border .2s;
}
.date-add-row input[type="date"]:focus { outline: none; border-color: var(--gold); }

/* date chips */
.date-chips { display: flex; flex-wrap: wrap; gap: 8px; min-height: 40px; }
.date-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #fef3c7;
  border: 1.5px solid #fcd34d;
  color: #92400e;
  border-radius: 20px;
  padding: 4px 12px 4px 14px;
  font-size: .82rem;
  font-weight: 500;
  font-family: 'Jost', sans-serif;
}
.date-chip button {
  background: none;
  border: none;
  cursor: pointer;
  color: #b45309;
  font-size: .9rem;
  line-height: 1;
  padding: 0;
  transition: color .15s;
}
.date-chip button:hover { color: #991b1b; }

.no-dates-note {
  font-size: .82rem;
  color: #999;
  font-style: italic;
  padding: 10px 0;
}
.any-note {
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 8px;
  padding: 8px 14px;
  font-size: .82rem;
  margin-top: 12px;
}

.modal-box::-webkit-scrollbar { width: 6px; }
.modal-box::-webkit-scrollbar-track { background: #f0ece4; }
.modal-box::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
.sub-modal-box::-webkit-scrollbar { width: 5px; }
.sub-modal-box::-webkit-scrollbar-track { background: #f5f5f5; }
.sub-modal-box::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
</style>
</head>
<body>

<div class="overlay">
<div class="container" style="max-width:900px;">

  <div class="page-header">
    <div class="page-title">💇 Manage Stylists</div>
    <a href="dashboard.php" class="btn-dash">← Dashboard</a>
  </div>

  <!-- ADD / EDIT STYLIST FORM -->
  <div class="card-custom">
    <div class="card-title"><?= $editData ? '✏️ Edit Stylist' : '＋ Add New Stylist' ?></div>
    <form method="POST" class="row g-2">
      <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

      <div class="col-md-4">
        <input type="text" name="name" placeholder="Stylist Name"
               class="form-control"
               value="<?= htmlspecialchars($editData['name'] ?? '') ?>"
               required>
      </div>

      <div class="col-md-4">
        <select name="service" class="form-select">
          <option value="">No Service Assigned</option>
          <?php
          $services = $conn->query("SELECT * FROM services");
          while ($s = $services->fetch_assoc()):
          ?>
          <option value="<?= $s['id'] ?>"
            <?= (isset($editData['service_id']) && $editData['service_id'] == $s['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['service_name']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-md-4 d-flex gap-2 align-items-center">
        <?php if ($editData): ?>
          <button name="update" class="btn-gold">Update</button>
          <a href="stylists.php" class="btn btn-secondary btn-sm">Cancel</a>
        <?php else: ?>
          <button name="add" class="btn-gold">Add Stylist</button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- STYLISTS TABLE -->
  <div class="card-custom">
    <div class="card-title">📋 All Stylists</div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Assigned Service</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $result = $conn->query("
          SELECT stylists.*, services.service_name
          FROM stylists
          LEFT JOIN services ON stylists.service_id = services.id
          ORDER BY stylists.name
      ");
      if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
      ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['service_name'] ?? 'Not Assigned') ?></td>
        <td>
          <a href="?edit=<?= $row['id'] ?>" class="btn-act btn-edit">Edit</a>

          <button class="btn-act btn-slots"
                  onclick="openSlotsModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')">
            🕐 Time Slots
          </button>

          <a href="?delete=<?= $row['id'] ?>"
             class="btn-act btn-delete"
             onclick="return confirm('Delete this stylist?')">Delete</a>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="4" style="color:#aaa;padding:24px;font-style:italic;">No stylists found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
</div>

<!-- ══════════════════════════════════
     TIME SLOTS MODAL
══════════════════════════════════ -->
<div class="modal-backdrop-custom" id="slotsModal">
  <div class="modal-box">

    <div class="modal-head">
      <div class="modal-head-title">🕐 Time Slots — <span id="modalStylistName"></span></div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>

    <div class="modal-body">

      <!-- SLOT FORM -->
      <div id="slotFormWrap">
        <div class="card-title" id="slotFormTitle" style="font-size:.95rem;">＋ Add Time Slot</div>
        <div class="slot-error"   id="slotError"></div>
        <div class="slot-success" id="slotSuccess"></div>

        <div class="slot-form-grid">
          <input type="hidden" id="slotId">
          <input type="hidden" id="modalStylistId">

          <div>
            <label>Day</label>
            <select id="slotDay">
              <?php foreach ($days as $d): ?>
              <option value="<?= $d ?>"><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Start Time</label>
            <input type="time" id="slotStart">
          </div>

          <div>
            <label>End Time</label>
            <input type="time" id="slotEnd">
          </div>

          <div>
            <label>Status</label>
            <select id="slotStatus">
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center;">
          <button class="btn-gold" id="slotSaveBtn" onclick="saveSlot()">Add Slot</button>
          <button class="btn-gold" id="slotCancelEditBtn"
                  style="display:none;background:#eee;color:#555;"
                  onclick="cancelSlotEdit()">Cancel</button>
        </div>
      </div>

      <!-- SLOTS TABLE -->
      <div class="slot-table-wrap">
        <div class="slot-section-label">Existing Time Slots</div>
        <div id="slotsTableContainer">
          <div class="loading-slots">Loading slots…</div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ══════════════════════════════════
     AVAILABLE DATES SUB-MODAL
══════════════════════════════════ -->
<div class="sub-modal-backdrop" id="datesModal">
  <div class="sub-modal-box">
    <div class="sub-modal-head">
      <div class="sub-modal-head-title">📅 Available Dates — <span id="datesSlotLabel"></span></div>
      <button class="modal-close" onclick="closeDatesModal()">✕</button>
    </div>
    <div class="sub-modal-body">

      <p style="font-size:.82rem;color:#666;margin-bottom:14px;">
        Add specific dates this slot is available. If <strong>no dates</strong> are added, the slot appears every matching <strong id="datesSlotDay"></strong> automatically.
      </p>

      <div class="date-add-row">
        <input type="date" id="newDateInput" min="<?= date('Y-m-d') ?>">
        <button class="btn-gold" style="padding:8px 18px;font-size:.85rem;" onclick="addDate()">Add</button>
      </div>

      <div class="slot-error" id="dateError" style="margin-bottom:10px;"></div>

      <div class="slot-section-label" style="font-size:.88rem;">Added Dates</div>
      <div class="date-chips" id="dateChips">
        <div class="loading-slots" style="padding:10px 0;">Loading…</div>
      </div>

      <div class="any-note" id="anyNote" style="display:none;">
        ℹ️ No specific dates set — this slot is available on every matching <strong id="anyNoteDay"></strong>.
      </div>

    </div>
  </div>
</div>

<script>
const DAYS_ORDER = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

let currentDatesSlotId   = null;
let currentDatesSlotDay  = '';
let currentDatesSlotTime = '';

function fmt(t) {
  if (!t) return '';
  const [h, m] = t.split(':');
  const hh = parseInt(h);
  const ampm = hh >= 12 ? 'PM' : 'AM';
  return `${hh % 12 || 12}:${m} ${ampm}`;
}

function fmtDate(d) {
  if (!d) return '';
  const dt = new Date(d + 'T00:00:00');
  return dt.toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
}

/* ── MAIN MODAL ── */
function openSlotsModal(stylistId, stylistName) {
  document.getElementById('modalStylistId').value    = stylistId;
  document.getElementById('modalStylistName').textContent = stylistName;
  document.getElementById('slotsModal').classList.add('open');
  resetSlotForm();
  loadSlots(stylistId);
}

function closeModal() {
  document.getElementById('slotsModal').classList.remove('open');
}

document.getElementById('slotsModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

/* ── LOAD SLOTS ── */
function loadSlots(stylistId) {
  document.getElementById('slotsTableContainer').innerHTML =
    '<div class="loading-slots">Loading…</div>';

  fetch(`stylists.php?get_slots=1&stylist_id=${stylistId}`)
    .then(r => r.json())
    .then(slots => renderSlots(slots))
    .catch(() => {
      document.getElementById('slotsTableContainer').innerHTML =
        '<div class="slot-empty">Failed to load slots.</div>';
    });
}

/* ── RENDER SLOTS ── */
function renderSlots(slots) {
  if (!slots.length) {
    document.getElementById('slotsTableContainer').innerHTML =
      '<div class="slot-empty">No time slots added yet.</div>';
    return;
  }

  const grouped = {};
  DAYS_ORDER.forEach(d => grouped[d] = []);
  slots.forEach(s => { if (grouped[s.day_of_week]) grouped[s.day_of_week].push(s); });

  let html = `<table class="slot-table">
    <thead><tr>
      <th>#</th><th>Day</th><th>Start</th><th>End</th><th>Status</th><th>Dates</th><th>Actions</th>
    </tr></thead><tbody>`;

  let counter = 1;
  DAYS_ORDER.forEach(day => {
    if (!grouped[day].length) return;
    html += `<tr class="day-header"><td colspan="7">📅 ${day}</td></tr>`;
    grouped[day].forEach(s => {
      const isAv    = s.status === 'available';
      const numDates = s.specific_dates ? s.specific_dates.length : 0;
      const datesBadge = numDates > 0
        ? `<span class="dates-badge">${numDates} date${numDates > 1 ? 's' : ''}</span>`
        : `<span class="dates-badge any">Every ${s.day_of_week}</span>`;

      html += `<tr id="slot-row-${s.id}">
        <td>${counter++}</td>
        <td>${s.day_of_week}</td>
        <td>${fmt(s.start_time)}</td>
        <td>${fmt(s.end_time)}</td>
        <td><span id="badge-${s.id}" class="${isAv ? 'badge-av' : 'badge-un'}">${isAv ? 'Available' : 'Unavailable'}</span></td>
        <td>${datesBadge}</td>
        <td>
          <button class="btn-sm-act s-edit" onclick="editSlot(${s.id},'${s.day_of_week}','${s.start_time}','${s.end_time}','${s.status}')">Edit</button>
          <button class="btn-sm-act s-dates" onclick="openDatesModal(${s.id},'${s.day_of_week}','${fmt(s.start_time)} – ${fmt(s.end_time)}')">📅 Dates</button>
          <button class="btn-sm-act ${isAv ? 's-tog-av' : 's-tog-un'}" id="tog-${s.id}" onclick="toggleSlot(${s.id})">${isAv ? 'Mark Unavailable' : 'Mark Available'}</button>
          <button class="btn-sm-act s-delete" onclick="deleteSlot(${s.id})">Delete</button>
        </td>
      </tr>`;
    });
  });

  html += '</tbody></table>';
  document.getElementById('slotsTableContainer').innerHTML = html;
}

/* ── SAVE SLOT ── */
function saveSlot() {
  const id        = document.getElementById('slotId').value;
  const stylistId = document.getElementById('modalStylistId').value;
  const day       = document.getElementById('slotDay').value;
  const start     = document.getElementById('slotStart').value;
  const end       = document.getElementById('slotEnd').value;
  const status    = document.getElementById('slotStatus').value;
  const errEl     = document.getElementById('slotError');
  const sucEl     = document.getElementById('slotSuccess');

  errEl.style.display = 'none';
  sucEl.style.display = 'none';

  if (!start || !end) {
    errEl.textContent = 'Please fill in both start and end time.';
    errEl.style.display = 'block';
    return;
  }

  const action = id ? 'update_slot' : 'add_slot';
  const body   = new FormData();
  body.append(action, '1');
  body.append('stylist_id', stylistId);
  body.append('day_of_week', day);
  body.append('start_time', start);
  body.append('end_time', end);
  body.append('status', status);
  if (id) body.append('id', id);

  fetch('stylists.php', { method: 'POST', body })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        sucEl.textContent   = id ? 'Slot updated!' : 'Slot added!';
        sucEl.style.display = 'block';
        setTimeout(() => sucEl.style.display = 'none', 2500);
        resetSlotForm();
        loadSlots(stylistId);
      } else {
        errEl.textContent = res.message;
        errEl.style.display = 'block';
      }
    });
}

function editSlot(id, day, start, end, status) {
  document.getElementById('slotId').value       = id;
  document.getElementById('slotDay').value      = day;
  document.getElementById('slotStart').value    = start;
  document.getElementById('slotEnd').value      = end;
  document.getElementById('slotStatus').value   = status;
  document.getElementById('slotFormTitle').textContent = '✏️ Edit Time Slot';
  document.getElementById('slotSaveBtn').textContent   = 'Update Slot';
  document.getElementById('slotCancelEditBtn').style.display = 'inline-block';
  document.getElementById('slotFormWrap').scrollIntoView({ behavior: 'smooth' });
}

function cancelSlotEdit() { resetSlotForm(); }

function resetSlotForm() {
  document.getElementById('slotId').value     = '';
  document.getElementById('slotDay').value    = 'Monday';
  document.getElementById('slotStart').value  = '';
  document.getElementById('slotEnd').value    = '';
  document.getElementById('slotStatus').value = 'available';
  document.getElementById('slotError').style.display   = 'none';
  document.getElementById('slotSuccess').style.display = 'none';
  document.getElementById('slotFormTitle').textContent = '＋ Add Time Slot';
  document.getElementById('slotSaveBtn').textContent   = 'Add Slot';
  document.getElementById('slotCancelEditBtn').style.display = 'none';
}

function toggleSlot(id) {
  const body = new FormData();
  body.append('toggle_slot', '1');
  body.append('id', id);
  fetch('stylists.php', { method: 'POST', body })
    .then(r => r.json())
    .then(res => {
      if (res.success) loadSlots(document.getElementById('modalStylistId').value);
    });
}

function deleteSlot(id) {
  if (!confirm('Delete this time slot?')) return;
  const stylistId = document.getElementById('modalStylistId').value;
  const body = new FormData();
  body.append('delete_slot', '1');
  body.append('id', id);
  fetch('stylists.php', { method: 'POST', body })
    .then(r => r.json())
    .then(res => { if (res.success) loadSlots(stylistId); });
}

/* ══════════════════════════════
   DATES SUB-MODAL
══════════════════════════════ */
function openDatesModal(slotId, slotDay, slotTimeLabel) {
  currentDatesSlotId   = slotId;
  currentDatesSlotDay  = slotDay;
  currentDatesSlotTime = slotTimeLabel;

  document.getElementById('datesSlotLabel').textContent = `${slotDay} · ${slotTimeLabel}`;
  document.getElementById('datesSlotDay').textContent   = slotDay;
  document.getElementById('anyNoteDay').textContent     = slotDay;
  document.getElementById('dateError').style.display    = 'none';
  document.getElementById('newDateInput').value         = '';
  document.getElementById('datesModal').classList.add('open');
  loadDates();
}

function closeDatesModal() {
  document.getElementById('datesModal').classList.remove('open');
  // reload main slots table to refresh date counts
  loadSlots(document.getElementById('modalStylistId').value);
}

document.getElementById('datesModal').addEventListener('click', function(e) {
  if (e.target === this) closeDatesModal();
});

function loadDates() {
  document.getElementById('dateChips').innerHTML = '<span style="color:#bbb;font-size:.82rem;">Loading…</span>';
  document.getElementById('anyNote').style.display = 'none';

  fetch(`stylists.php?get_slot_dates=1&timeslot_id=${currentDatesSlotId}`)
    .then(r => r.json())
    .then(dates => renderDateChips(dates))
    .catch(() => {
      document.getElementById('dateChips').innerHTML = '<span style="color:#e00;font-size:.82rem;">Failed to load.</span>';
    });
}

function renderDateChips(dates) {
  const container = document.getElementById('dateChips');
  const anyNote   = document.getElementById('anyNote');

  if (!dates.length) {
    container.innerHTML = `<p class="no-dates-note">No specific dates — available every ${currentDatesSlotDay}.</p>`;
    anyNote.style.display = 'block';
    return;
  }

  anyNote.style.display = 'none';
  container.innerHTML = '';
  dates.forEach(d => {
    const chip = document.createElement('div');
    chip.className = 'date-chip';
    chip.innerHTML = `
      <span>${fmtDate(d.available_date)}</span>
      <button onclick="removeDate(${d.id})" title="Remove">✕</button>
    `;
    container.appendChild(chip);
  });
}

function addDate() {
  const dateVal = document.getElementById('newDateInput').value;
  const errEl   = document.getElementById('dateError');
  errEl.style.display = 'none';

  if (!dateVal) {
    errEl.textContent = 'Please select a date.';
    errEl.style.display = 'block';
    return;
  }

  const body = new FormData();
  body.append('add_slot_date', '1');
  body.append('timeslot_id', currentDatesSlotId);
  body.append('available_date', dateVal);

  fetch('stylists.php', { method: 'POST', body })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        document.getElementById('newDateInput').value = '';
        loadDates();
      } else {
        errEl.textContent = res.message;
        errEl.style.display = 'block';
      }
    });
}

function removeDate(dateRowId) {
  if (!confirm('Remove this date?')) return;
  const body = new FormData();
  body.append('remove_slot_date', '1');
  body.append('id', dateRowId);
  fetch('stylists.php', { method: 'POST', body })
    .then(r => r.json())
    .then(res => { if (res.success) loadDates(); });
}
</script>

</body>
</html>