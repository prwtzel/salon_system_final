<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: customer_login.php");
    exit();
}

$user_id       = $_SESSION['user_id'];
$customer_name = $_SESSION['name'];

$success = "";
$notif   = "";

/* =========================
   NOTIFICATION
========================= */
$checkNotif = $conn->query("
    SELECT * FROM appointments
    WHERE user_id='$user_id'
    AND status='Approved'
    AND notif_seen=0
    ORDER BY id DESC
    LIMIT 1
");
if ($checkNotif && $checkNotif->num_rows > 0) {
    $rowNotif = $checkNotif->fetch_assoc();
    $notif    = "🎉 Your appointment on " . $rowNotif['appointment_date'] . " has been APPROVED!";
    $conn->query("UPDATE appointments SET notif_seen=1 WHERE id=" . $rowNotif['id']);
}

/* =========================
   AJAX: GET STYLISTS BY SERVICE
========================= */
if (isset($_GET['get_stylists'])) {
    $service_id = intval($_GET['service_id']);
    $result = $conn->query("
        SELECT * FROM stylists
        WHERE service_id='$service_id'
        ORDER BY name
    ");
    $list = [];
    while ($r = $result->fetch_assoc()) {
        $list[] = ['id' => $r['id'], 'name' => $r['name']];
    }
    echo json_encode($list);
    exit();
}

/* =========================
   AJAX: GET AVAILABLE DATES
   For a stylist, look ahead 60 days.
   A date is available if:
     - The stylist has at least one slot for that day_of_week with status='available'
     - AND that slot is not fully booked on that date
     - AND (no specific dates set for the slot OR that date is in stylist_timeslot_dates)
========================= */
if (isset($_GET['get_available_dates'])) {
    $stylist_id = intval($_GET['stylist_id']);
    $availableDates = [];

    $today    = new DateTime();
    $end      = (new DateTime())->modify('+60 days');
    $interval = new DateInterval('P1D');
    $period   = new DatePeriod($today, $interval, $end);

    // get all active slots for this stylist
    $slotsRes = $conn->query("
        SELECT t.*, GROUP_CONCAT(d.available_date ORDER BY d.available_date) AS specific_dates
        FROM stylist_timeslots t
        LEFT JOIN stylist_timeslot_dates d ON d.timeslot_id = t.id
        WHERE t.stylist_id = '$stylist_id'
        AND   t.status = 'available'
        GROUP BY t.id
    ");

    $slots = [];
    while ($s = $slotsRes->fetch_assoc()) {
        $s['dates_list'] = $s['specific_dates'] ? explode(',', $s['specific_dates']) : [];
        $slots[] = $s;
    }

    foreach ($period as $dt) {
        $dateStr = $dt->format('Y-m-d');
        $dow     = $dt->format('l'); // e.g. Monday

        $hasOpen = false;
        foreach ($slots as $slot) {
            if ($slot['day_of_week'] !== $dow) continue;

            // check specific dates restriction
            if (!empty($slot['dates_list']) && !in_array($dateStr, $slot['dates_list'])) continue;

            // check not already booked
            $slotId    = $slot['id'];
            $startTime = $slot['start_time'];
            $booked = $conn->query("
                SELECT id FROM appointments
                WHERE stylist_id       = '$stylist_id'
                AND   appointment_date = '$dateStr'
                AND   appointment_time = '$startTime'
                AND   status          IN ('Pending','Approved')
                LIMIT 1
            ");
            if ($booked->num_rows === 0) {
                $hasOpen = true;
                break;
            }
        }

        if ($hasOpen) {
            $availableDates[] = [
                'value' => $dateStr,
                'label' => $dt->format('D, M j, Y') . ' (' . $dow . ')'
            ];
        }
    }

    echo json_encode($availableDates);
    exit();
}

/* =========================
   AJAX: GET AVAILABLE SLOTS FOR DATE
========================= */
if (isset($_GET['get_slots'])) {
    $stylist_id = intval($_GET['stylist_id']);
    $date       = $conn->real_escape_string($_GET['date']);
    $dow        = date('l', strtotime($date));

    $result = $conn->query("
        SELECT t.*
        FROM stylist_timeslots t
        WHERE t.stylist_id = '$stylist_id'
        AND   t.day_of_week = '$dow'
        AND   t.status      = 'available'
        AND NOT EXISTS (
            SELECT 1 FROM appointments a
            WHERE a.stylist_id       = t.stylist_id
            AND   a.appointment_date = '$date'
            AND   a.appointment_time = t.start_time
            AND   a.status          IN ('Pending','Approved')
        )
        AND (
            -- no specific dates set (available every matching weekday)
            NOT EXISTS (
                SELECT 1 FROM stylist_timeslot_dates d WHERE d.timeslot_id = t.id
            )
            OR
            -- OR this specific date is listed
            EXISTS (
                SELECT 1 FROM stylist_timeslot_dates d
                WHERE d.timeslot_id    = t.id
                AND   d.available_date = '$date'
            )
        )
        ORDER BY t.start_time
    ");

    $slots = [];
    while ($r = $result->fetch_assoc()) {
        $slots[] = [
            'id'         => $r['id'],
            'start_time' => $r['start_time'],
            'end_time'   => $r['end_time'],
            'label'      => date('h:i A', strtotime($r['start_time']))
                          . ' – '
                          . date('h:i A', strtotime($r['end_time']))
        ];
    }
    echo json_encode($slots);
    exit();
}

/* =========================
   BOOK
========================= */
if (isset($_POST['submit'])) {
    $name       = $conn->real_escape_string($_POST['name']);
    $service    = intval($_POST['service']);
    $stylist    = intval($_POST['stylist']);
    $date       = $conn->real_escape_string($_POST['date']);
    $slot_id    = intval($_POST['slot_id']);
    $start_time = $conn->real_escape_string($_POST['start_time']);

    if ($name && $service && $stylist && $date && $slot_id && $start_time) {

        $dow   = date('l', strtotime($date));
        $check = $conn->query("
            SELECT t.id FROM stylist_timeslots t
            WHERE t.id          = '$slot_id'
            AND   t.stylist_id  = '$stylist'
            AND   t.day_of_week = '$dow'
            AND   t.status      = 'available'
            AND NOT EXISTS (
                SELECT 1 FROM appointments a
                WHERE a.stylist_id       = t.stylist_id
                AND   a.appointment_date = '$date'
                AND   a.appointment_time = t.start_time
                AND   a.status          IN ('Pending','Approved')
            )
            AND (
                NOT EXISTS (SELECT 1 FROM stylist_timeslot_dates d WHERE d.timeslot_id = t.id)
                OR
                EXISTS (SELECT 1 FROM stylist_timeslot_dates d WHERE d.timeslot_id = t.id AND d.available_date = '$date')
            )
            LIMIT 1
        ");

        if ($check && $check->num_rows > 0) {
            $conn->query("
                INSERT INTO appointments
                    (user_id, customer_name, service_id, stylist_id,
                     appointment_date, appointment_time, status)
                VALUES
                    ('$user_id','$name','$service','$stylist',
                     '$date','$start_time','Pending')
            ");
            header("Location: customer_dashboard.php?msg=booked");
            exit();
        } else {
            $notif = "⚠️ That slot was just taken. Please choose another.";
        }

    } else {
        $notif = "⚠️ All fields are required!";
    }
}

/* =========================
   CANCEL
========================= */
if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    $conn->query("
        UPDATE appointments
        SET status='Cancelled'
        WHERE id='$id' AND user_id='$user_id'
    ");
    header("Location: customer_dashboard.php?msg=cancel");
    exit();
}

/* =========================
   DELETE
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("
        DELETE FROM appointments
        WHERE id='$id' AND user_id='$user_id'
    ");
    header("Location: customer_dashboard.php?msg=deleted");
    exit();
}

/* =========================
   MESSAGES
========================= */
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'booked')  $success = "✅ Appointment booked successfully!";
    if ($_GET['msg'] == 'cancel')  $notif   = "Booking cancelled.";
    if ($_GET['msg'] == 'deleted') $notif   = "Appointment deleted.";
}

/* =========================
   APPOINTMENTS
========================= */
$appointments = $conn->query("
    SELECT a.*,
           s.service_name,
           st.name AS stylist_name
    FROM appointments a
    LEFT JOIN services  s  ON a.service_id  = s.id
    LEFT JOIN stylists  st ON a.stylist_id  = st.id
    WHERE a.user_id = '$user_id'
    ORDER BY a.id DESC
");

$services_res = $conn->query("SELECT * FROM services ORDER BY service_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Glow Salon — Dashboard</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family: Segoe UI, sans-serif; }

body {
    background: url('assets/img/bg.jpg') no-repeat center center fixed;
    background-size: cover;
}
body::before {
    content:"";
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.60);
    z-index:0;
}

.topbar {
    position:relative;
    z-index:2;
    display:flex;
    justify-content:space-between;
    padding:15px 20px;
    color:white;
    font-size:1rem;
}
.topbar a { color:white; text-decoration:none; }

.title {
    position:relative;
    z-index:2;
    text-align:center;
    color:white;
    font-size:28px;
    margin-bottom:10px;
}

.alert {
    position:relative;
    z-index:2;
    width:85%;
    margin:8px auto;
    padding:10px 16px;
    border-radius:10px;
    text-align:center;
    font-size:.93rem;
}
.success { background:rgba(0,200,80,0.18); color:#b6ffb6; border:1px solid rgba(0,255,100,0.2); }
.error   { background:rgba(255,60,60,0.18); color:#ffb3b3; border:1px solid rgba(255,80,80,0.2); }

.container {
    position:relative;
    z-index:2;
    width:94%;
    margin:16px auto 40px;
    display:grid;
    grid-template-columns:1fr 2fr;
    gap:20px;
}

@media(max-width:700px){
    .container { grid-template-columns:1fr; }
}

.card {
    background:rgba(255,255,255,0.11);
    backdrop-filter:blur(14px);
    padding:22px 20px;
    border-radius:16px;
    color:white;
    border:1px solid rgba(255,255,255,0.12);
}
.card h3 {
    margin-bottom:14px;
    font-size:1.1rem;
    letter-spacing:.3px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    padding-bottom:10px;
}

.form-group { margin-bottom:10px; }
.form-group label {
    display:block;
    font-size:.78rem;
    margin-bottom:4px;
    color:rgba(255,255,255,.7);
    text-transform:uppercase;
    letter-spacing:.5px;
}

input, select {
    width:100%;
    padding:10px 12px;
    border-radius:9px;
    border:1.5px solid rgba(255,255,255,0.2);
    background:rgba(255,255,255,0.12);
    color:white;
    font-size:.9rem;
    transition:border .2s;
}
input:focus, select:focus {
    outline:none;
    border-color:#ff4d6d;
    background:rgba(255,255,255,0.18);
}
select option { background:#333; color:white; }

input[readonly] {
    opacity:.6;
    cursor:not-allowed;
}

/* step indicator */
.step-row {
    display:flex;
    gap:6px;
    margin-bottom:14px;
    flex-wrap:wrap;
}
.step-pill {
    font-size:.7rem;
    padding:3px 10px;
    border-radius:20px;
    font-weight:600;
    letter-spacing:.3px;
    text-transform:uppercase;
    transition:all .25s;
}
.step-pill.done    { background:#10b981; color:white; }
.step-pill.active  { background:#ff4d6d; color:white; }
.step-pill.waiting { background:rgba(255,255,255,.12); color:rgba(255,255,255,.4); }

/* info box for selected values */
.selected-info {
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.15);
    border-radius:9px;
    padding:8px 12px;
    font-size:.8rem;
    color:rgba(255,255,255,.7);
    margin-bottom:10px;
    display:none;
}
.selected-info span { color:white; font-weight:600; }

.slot-hint {
    font-size:.78rem;
    color:rgba(255,255,255,.5);
    margin-top:4px;
    font-style:italic;
}

.btn-book {
    width:100%;
    padding:11px;
    background:#ff4d6d;
    border:none;
    color:white;
    border-radius:10px;
    font-size:.95rem;
    font-weight:600;
    cursor:pointer;
    margin-top:6px;
    transition:background .2s, transform .1s;
}
.btn-book:hover  { background:#e83558; }
.btn-book:active { transform:scale(.98); }
.btn-book:disabled { background:#888; cursor:not-allowed; }

/* loading indicator inside select */
.select-loading {
    pointer-events:none;
    opacity:.7;
}

/* no-dates message */
.no-dates-msg {
    font-size:.82rem;
    color:#ffb3b3;
    margin-top:5px;
    padding:8px 12px;
    background:rgba(255,60,60,0.12);
    border-radius:8px;
    display:none;
}

/* ── TABLE ── */
.table-wrap { overflow-x:auto; }

table {
    width:100%;
    border-collapse:collapse;
    border-radius:10px;
    overflow:hidden;
    font-size:.86rem;
}
th {
    background:#ff4d6d;
    color:white;
    padding:10px 12px;
    text-align:center;
}
td {
    text-align:center;
    padding:9px 10px;
    background:rgba(255,255,255,0.88);
    color:#222;
    border-bottom:1px solid #eee;
}
tr:last-child td { border-bottom:none; }

.status {
    padding:4px 10px;
    border-radius:6px;
    color:white;
    font-size:.78rem;
    font-weight:600;
}

.act-link {
    color:#cc2244;
    cursor:pointer;
    font-size:.8rem;
    background:none;
    border:none;
    padding:0;
    font-family:inherit;
    text-decoration:underline;
}
.act-link:hover { color:#ff4d6d; }

.no-appt {
    text-align:center;
    padding:20px;
    color:rgba(255,255,255,.45);
    font-style:italic;
    font-size:.88rem;
}

.time-badge {
    font-size:.78rem;
    background:#f0f0f0;
    border-radius:5px;
    padding:2px 7px;
    color:#555;
}

/* ── MODAL ── */
.modal {
    display:none;
    position:fixed;
    z-index:999;
    inset:0;
    background:rgba(0,0,0,0.72);
    align-items:center;
    justify-content:center;
}
.modal.open { display:flex; }

.modal-content {
    background:white;
    width:310px;
    padding:28px 24px;
    border-radius:14px;
    text-align:center;
    box-shadow:0 10px 40px rgba(0,0,0,.4);
}
.modal-content p { color:#333; font-size:.95rem; margin-bottom:18px; }
.modal-btns { display:flex; gap:10px; justify-content:center; }
.modal-btns button {
    padding:9px 24px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    font-size:.88rem;
    width:auto;
}
.btn-yes { background:#ff4d6d; color:white; }
.btn-no  { background:#e5e5e5; color:#555; }
</style>
</head>
<body>

<div class="topbar">
    <div>💇 Glow Salon</div>
    <div><a href="logout.php">Logout</a></div>
</div>

<div class="title">Welcome, <?= htmlspecialchars($customer_name) ?></div>

<?php if (!empty($notif)): ?>
<div class="alert error"><?= htmlspecialchars($notif) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
<div class="alert success"><?= $success ?></div>
<?php endif; ?>

<div class="container">

  <!-- ══ BOOKING FORM ══ -->
  <div class="card">
    <h3>📅 Book Appointment</h3>

    <!-- Step pills -->
    <div class="step-row">
      <div class="step-pill active"  id="pill1">1 · Service</div>
      <div class="step-pill waiting" id="pill2">2 · Stylist</div>
      <div class="step-pill waiting" id="pill3">3 · Date</div>
      <div class="step-pill waiting" id="pill4">4 · Time</div>
    </div>

    <form method="POST" id="bookForm">

      <!-- NAME (readonly) -->
      <div class="form-group">
        <label>Your Name</label>
        <input type="text" name="name"
               value="<?= htmlspecialchars($customer_name) ?>"
               readonly>
      </div>

      <!-- SERVICE -->
      <div class="form-group">
        <label>1. Choose Service</label>
        <select name="service" id="serviceSelect" required onchange="onServiceChange()">
          <option value="">— Select Service —</option>
          <?php while ($s = $services_res->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['service_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- STYLIST -->
      <div class="form-group">
        <label>2. Choose Stylist</label>
        <select name="stylist" id="stylistSelect" required onchange="onStylistChange()" disabled>
          <option value="">— Pick a service first —</option>
        </select>
      </div>

      <!-- DATE DROPDOWN (auto-generated) -->
      <div class="form-group">
        <label>3. Choose Available Date</label>
        <select name="date" id="dateSelect" required onchange="onDateChange()" disabled>
          <option value="">— Pick a stylist first —</option>
        </select>
        <div class="no-dates-msg" id="noDatesMsg">
          No available dates in the next 60 days for this stylist.
        </div>
        <div class="slot-hint" id="dateHint"></div>
      </div>

      <!-- SELECTED DATE INFO -->
      <div class="selected-info" id="selectedDateInfo">
        Showing slots for: <span id="selectedDateLabel"></span>
      </div>

      <!-- TIME SLOT -->
      <div class="form-group">
        <label>4. Choose Time Slot</label>
        <select name="slot_display" id="slotSelect" required disabled>
          <option value="">— Pick a date first —</option>
        </select>
        <div class="slot-hint" id="slotHint"></div>
        <input type="hidden" name="slot_id"    id="slotIdInput">
        <input type="hidden" name="start_time" id="startTimeInput">
      </div>

      <button type="submit" name="submit" class="btn-book" id="bookBtn" disabled>
        Book Now
      </button>

    </form>
  </div>

  <!-- ══ APPOINTMENTS TABLE ══ -->
  <div class="card">
    <h3>🗓 My Appointments</h3>
    <div class="table-wrap">
    <?php if ($appointments && $appointments->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Service</th>
          <th>Stylist</th>
          <th>Date</th>
          <th>Time</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = $appointments->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['service_name'] ?? '—') ?></td>
        <td><?= htmlspecialchars($row['stylist_name'] ?? '—') ?></td>
        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
        <td>
          <?php if (!empty($row['appointment_time'])): ?>
          <span class="time-badge">
            <?= date('h:i A', strtotime($row['appointment_time'])) ?>
          </span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <span class="status" style="background:
            <?= $row['status']=='Pending'  ? '#f59e0b'
              : ($row['status']=='Approved' ? '#10b981'
              : '#ef4444') ?>">
            <?= $row['status'] ?>
          </span>
        </td>
        <td>
          <?php if ($row['status'] === 'Pending'): ?>
            <button class="act-link" onclick="openModal('cancel',<?= $row['id'] ?>)">Cancel</button>
            &nbsp;|&nbsp;
          <?php endif; ?>
          <button class="act-link" onclick="openModal('delete',<?= $row['id'] ?>)">Delete</button>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="no-appt">No appointments yet.</div>
    <?php endif; ?>
    </div>
  </div>

</div>

<!-- CONFIRM MODAL -->
<div class="modal" id="modal">
  <div class="modal-content">
    <p id="modalText">Are you sure?</p>
    <div class="modal-btns">
      <button class="btn-yes" id="yesBtn">Yes</button>
      <button class="btn-no"  onclick="closeModal()">No</button>
    </div>
  </div>
</div>

<script>
/* ══ MODAL ══ */
let actionType = "", actionId = "";

function openModal(type, id) {
    actionType = type;
    actionId   = id;
    document.getElementById('modalText').innerText =
        type === 'delete'
        ? 'Are you sure you want to DELETE this appointment?'
        : 'Are you sure you want to CANCEL this appointment?';
    document.getElementById('modal').classList.add('open');
}
function closeModal() {
    document.getElementById('modal').classList.remove('open');
}
document.getElementById('yesBtn').onclick = function () {
    window.location.href = '?' + actionType + '=' + actionId;
};
document.getElementById('modal').addEventListener('click', function(e){
    if (e.target === this) closeModal();
});

/* ══ STEP PILLS ══ */
function updatePills(step) {
    for (let i = 1; i <= 4; i++) {
        const p = document.getElementById('pill' + i);
        if (i < step)       p.className = 'step-pill done';
        else if (i === step) p.className = 'step-pill active';
        else                 p.className = 'step-pill waiting';
    }
}

/* ══ ELEMENTS ══ */
const serviceSelect     = document.getElementById('serviceSelect');
const stylistSelect     = document.getElementById('stylistSelect');
const dateSelect        = document.getElementById('dateSelect');
const slotSelect        = document.getElementById('slotSelect');
const slotHint          = document.getElementById('slotHint');
const dateHint          = document.getElementById('dateHint');
const slotIdInput       = document.getElementById('slotIdInput');
const startTimeIn       = document.getElementById('startTimeInput');
const bookBtn           = document.getElementById('bookBtn');
const noDatesMsg        = document.getElementById('noDatesMsg');
const selectedDateInfo  = document.getElementById('selectedDateInfo');
const selectedDateLabel = document.getElementById('selectedDateLabel');

/* ══ RESET HELPERS ══ */
function resetStylists() {
    stylistSelect.innerHTML = '<option value="">— Pick a service first —</option>';
    stylistSelect.disabled  = true;
    resetDates();
}

function resetDates(msg) {
    dateSelect.innerHTML = '<option value="">' + (msg || '— Pick a stylist first —') + '</option>';
    dateSelect.disabled  = true;
    noDatesMsg.style.display   = 'none';
    dateHint.textContent       = '';
    selectedDateInfo.style.display = 'none';
    resetSlots();
}

function resetSlots(msg) {
    slotSelect.innerHTML = '<option value="">' + (msg || '— Pick a date first —') + '</option>';
    slotSelect.disabled  = true;
    slotIdInput.value    = '';
    startTimeIn.value    = '';
    slotHint.textContent = '';
    bookBtn.disabled     = true;
}

/* ══ 1 — SERVICE CHANGED ══ */
function onServiceChange() {
    const sid = serviceSelect.value;
    resetStylists();
    updatePills(sid ? 2 : 1);
    if (!sid) return;

    stylistSelect.innerHTML = '<option value="">Loading…</option>';
    stylistSelect.disabled  = true;

    fetch('customer_dashboard.php?get_stylists=1&service_id=' + sid)
        .then(r => r.json())
        .then(list => {
            if (!list.length) {
                stylistSelect.innerHTML = '<option value="">No stylists for this service</option>';
                return;
            }
            stylistSelect.innerHTML = '<option value="">— Select Stylist —</option>';
            list.forEach(st => {
                const o = document.createElement('option');
                o.value       = st.id;
                o.textContent = st.name;
                stylistSelect.appendChild(o);
            });
            stylistSelect.disabled = false;
        })
        .catch(() => {
            stylistSelect.innerHTML = '<option value="">Error loading stylists</option>';
        });
}

/* ══ 2 — STYLIST CHANGED → load available dates ══ */
function onStylistChange() {
    const stylistId = stylistSelect.value;
    resetDates();
    updatePills(stylistId ? 3 : 2);
    if (!stylistId) return;

    dateSelect.innerHTML = '<option value="">Loading available dates…</option>';
    dateSelect.disabled  = true;
    noDatesMsg.style.display = 'none';
    dateHint.textContent = '';

    fetch(`customer_dashboard.php?get_available_dates=1&stylist_id=${stylistId}`)
        .then(r => r.json())
        .then(dates => {
            if (!dates.length) {
                dateSelect.innerHTML = '<option value="">No available dates</option>';
                noDatesMsg.style.display = 'block';
                dateHint.textContent = '';
                return;
            }
            dateSelect.innerHTML = '<option value="">— Choose a Date —</option>';
            dates.forEach(d => {
                const o = document.createElement('option');
                o.value       = d.value;
                o.textContent = d.label;
                dateSelect.appendChild(o);
            });
            dateSelect.disabled  = false;
            dateHint.textContent = dates.length + ' date(s) available in the next 60 days';
        })
        .catch(() => {
            dateSelect.innerHTML = '<option value="">Error loading dates</option>';
        });
}

/* ══ 3 — DATE CHANGED → load slots ══ */
function onDateChange() {
    const stylistId = stylistSelect.value;
    const date      = dateSelect.value;
    resetSlots();

    if (!date) {
        selectedDateInfo.style.display = 'none';
        updatePills(3);
        return;
    }

    // show selected date info
    const selOpt = dateSelect.options[dateSelect.selectedIndex];
    selectedDateLabel.textContent  = selOpt.textContent;
    selectedDateInfo.style.display = 'block';
    updatePills(4);

    slotSelect.innerHTML = '<option value="">Loading time slots…</option>';
    slotSelect.disabled  = true;
    slotHint.textContent = '';

    fetch(`customer_dashboard.php?get_slots=1&stylist_id=${stylistId}&date=${date}`)
        .then(r => r.json())
        .then(slots => {
            if (!slots.length) {
                slotSelect.innerHTML = '<option value="">No slots available</option>';
                slotHint.textContent = 'All slots for this date are taken. Try another date.';
                slotHint.style.color = '#ffb3b3';
                return;
            }
            slotSelect.innerHTML = '<option value="">— Choose a Time —</option>';
            slots.forEach(sl => {
                const o = document.createElement('option');
                o.value          = sl.id;
                o.dataset.start  = sl.start_time;
                o.textContent    = sl.label;
                slotSelect.appendChild(o);
            });
            slotSelect.disabled  = false;
            slotHint.textContent = slots.length + ' slot(s) available';
            slotHint.style.color = 'rgba(255,255,255,.5)';
        })
        .catch(() => {
            slotSelect.innerHTML = '<option value="">Error loading slots</option>';
        });
}

/* ══ 4 — SLOT CHOSEN ══ */
slotSelect.addEventListener('change', function () {
    const sel = slotSelect.options[slotSelect.selectedIndex];
    if (!sel || !sel.value) {
        slotIdInput.value = '';
        startTimeIn.value = '';
        bookBtn.disabled  = true;
        return;
    }
    slotIdInput.value = sel.value;
    startTimeIn.value = sel.dataset.start;
    bookBtn.disabled  = false;
});
</script>
</body>
</html>