<?php
// customer/booking.php — Customer books an event (package OR custom items)
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    redirect('../auth/login.php');
}
$pageTitle   = 'New Booking';
$currentPage = 'booking';
$uid         = (int)$_SESSION['user_id'];
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_type   = trim($_POST['event_type']     ?? '');
    $event_date   = trim($_POST['event_date']     ?? '');
    $location     = trim($_POST['event_location'] ?? '');
    $num_guests   = (int)($_POST['num_guests']    ?? 0);
    $booking_type = trim($_POST['booking_type']   ?? 'package');
    $pkg_raw      = trim($_POST['package_id']     ?? '');
    $package_id   = ($booking_type === 'package' && $pkg_raw !== '' && (int)$pkg_raw > 0)
                    ? (int)$pkg_raw : null;
    $notes        = trim($_POST['special_notes']  ?? '');

    // Validation
    if ($event_type === '')  $errors[] = 'Event type is required.';
    if ($event_date === '')  $errors[] = 'Event date is required.';
    if ($event_date !== '' && $event_date <= date('Y-m-d'))
                             $errors[] = 'Event date must be in the future.';
    if ($num_guests < 1)     $errors[] = 'Number of guests must be at least 1.';

    // Collect custom items
    $selected_items = [];
    if ($booking_type === 'custom') {
        $raw_items = $_POST['items'] ?? [];
        foreach ($raw_items as $iid => $qty) {
            $iid = (int)$iid; $qty = (int)$qty;
            if ($iid > 0 && $qty > 0) $selected_items[$iid] = $qty;
        }
        if (empty($selected_items))
            $errors[] = 'Please select at least one menu item.';
    }

    // Calculate total
    $total = 0.0;
    if ($booking_type === 'package' && $package_id !== null) {
        $sp = $conn->prepare("SELECT price FROM packages WHERE id=? AND available=1");
        $sp->bind_param('i', $package_id);
        $sp->execute();
        $pr = $sp->get_result()->fetch_assoc();
        $sp->close();
        if ($pr) $total = (float)$pr['price'] * $num_guests;
    } elseif ($booking_type === 'custom' && !empty($selected_items)) {
        foreach ($selected_items as $iid => $qty) {
            $si = $conn->prepare("SELECT price FROM menu_items WHERE id=? AND available=1");
            $si->bind_param('i', $iid);
            $si->execute();
            $ir = $si->get_result()->fetch_assoc();
            $si->close();
            if ($ir) $total += (float)$ir['price'] * $qty;
        }
    }

    if (empty($errors)) {
        // Build INSERT using real_escape_string to handle nullable package_id cleanly
        $et  = $conn->real_escape_string($event_type);
        $ed  = $conn->real_escape_string($event_date);
        $el  = $conn->real_escape_string($location);
        $ng  = (int)$num_guests;
        $pi  = ($package_id === null) ? 'NULL' : (int)$package_id;
        $sn  = $conn->real_escape_string($notes);
        $ta  = number_format($total, 2, '.', '');

        $ok = $conn->query(
            "INSERT INTO orders (customer_id,event_type,event_date,event_location,num_guests,package_id,special_notes,total_amount)
             VALUES ($uid,'$et','$ed','$el',$ng,$pi,'$sn',$ta)"
        );

        if ($ok) {
            $new_order_id = $conn->insert_id;

            // Insert custom item details into order_details
            if ($booking_type === 'custom' && !empty($selected_items)) {
                foreach ($selected_items as $iid => $qty) {
                    $si = $conn->prepare("SELECT price FROM menu_items WHERE id=?");
                    $si->bind_param('i', $iid);
                    $si->execute();
                    $ir = $si->get_result()->fetch_assoc();
                    $si->close();
                    if ($ir) {
                        $unit = (float)$ir['price'];
                        $sd   = $conn->prepare(
                            "INSERT INTO order_details (order_id,item_id,quantity,unit_price) VALUES (?,?,?,?)"
                        );
                        $sd->bind_param('iiid', $new_order_id, $iid, $qty, $unit);
                        $sd->execute();
                        $sd->close();
                    }
                }
            }

            setFlash('success', 'Booking #' . $new_order_id . ' submitted! We will contact you shortly.');
            redirect('my_orders.php');
        } else {
            $errors[] = 'Booking failed: ' . $conn->error . '. Please try again.';
        }
    }
}

// Fetch packages
$packages_r  = $conn->query("SELECT * FROM packages WHERE available=1 ORDER BY price ASC");
$pkgs_list   = [];
while ($pk = $packages_r->fetch_assoc()) { $pkgs_list[] = $pk; }

// Fetch menu items grouped by category
$items_r     = $conn->query("SELECT * FROM menu_items WHERE available=1 ORDER BY category,name");
$menu_by_cat = [];
while ($mi = $items_r->fetch_assoc()) {
    $menu_by_cat[$mi['category']][] = $mi;
}

$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_customer.php';
?>
<style>
.booking-tabs { display:flex; gap:0; margin-bottom:var(--space-lg); border-radius:var(--radius-md); overflow:hidden; border:1.5px solid var(--border); }
.booking-tab  { flex:1; padding:0.75rem; text-align:center; font-size:0.875rem; font-weight:600; cursor:pointer; background:var(--bg-light); color:var(--text-muted); border:none; transition:var(--transition); }
.booking-tab.active { background:var(--primary); color:#fff; }
.booking-tab:hover:not(.active) { background:var(--accent-subtle); color:var(--primary); }

.pkg-radio-card { display:flex; gap:1rem; align-items:flex-start; padding:1rem; border:2px solid var(--border); border-radius:var(--radius-lg); cursor:pointer; transition:var(--transition); background:#fff; }
.pkg-radio-card:hover { border-color:var(--accent); }
.pkg-radio-card.selected { border-color:var(--primary); background:var(--accent-subtle); }
.pkg-radio-card img { width:90px; height:70px; border-radius:var(--radius-md); object-fit:cover; flex-shrink:0; }
.pkg-radio-card .pkg-info .pkg-title { font-family:var(--font-display); font-size:1.1rem; font-weight:700; }
.pkg-radio-card .pkg-info .pkg-price { color:var(--primary); font-weight:700; font-size:0.95rem; margin:2px 0; }
.pkg-radio-card .pkg-info .pkg-cap   { font-size:0.75rem; color:var(--text-muted); }
.pkg-radio-card .pkg-info .pkg-desc  { font-size:0.78rem; color:var(--text-muted); margin-top:4px; line-height:1.5; }
.pkg-radio-card input[type=radio]    { margin-top:4px; flex-shrink:0; accent-color:var(--primary); width:16px; height:16px; }

.menu-item-row { display:flex; align-items:center; gap:0.75rem; padding:0.6rem; border:1.5px solid var(--border); border-radius:var(--radius-md); background:#fff; transition:border-color var(--transition); }
.menu-item-row:hover { border-color:var(--border-dark); }
.menu-item-row.has-qty { border-color:var(--primary); background:var(--accent-subtle); }
.menu-item-row img { width:50px; height:50px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0; }
.item-info { flex:1; min-width:0; }
.item-info .i-name  { font-weight:600; font-size:0.875rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.item-info .i-price { color:var(--primary); font-size:0.8rem; font-weight:700; }
.qty-wrap  { display:flex; align-items:center; gap:4px; flex-shrink:0; }
.qty-btn   { width:28px; height:28px; border:1.5px solid var(--border); background:#fff; border-radius:var(--radius-sm); cursor:pointer; font-size:1rem; display:grid; place-items:center; transition:var(--transition); line-height:1; padding:0; }
.qty-btn:hover { background:var(--primary); color:#fff; border-color:var(--primary); }
.qty-num   { width:36px; text-align:center; font-size:0.875rem; font-weight:600; border:1.5px solid var(--border); border-radius:var(--radius-sm); padding:0.2rem; }
.qty-num:focus { outline:none; border-color:var(--primary); }
.cat-header { font-size:0.7rem; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:var(--text-muted); padding:var(--space-md) 0 var(--space-sm); border-bottom:1px solid var(--border); margin-bottom:var(--space-sm); }
.items-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; }
@media(max-width:600px){ .items-grid{ grid-template-columns:1fr; } }
</style>

<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">📅 New Booking</span>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i><span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert-flash error">
        <i class="fas fa-exclamation-circle"></i>
        <div><?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?></div>
      </div>
    <?php endif; ?>

    <div class="booking-layout">
      <form method="POST" action="" id="bookingForm">
        <input type="hidden" name="booking_type" id="booking_type"
               value="<?= e($_POST['booking_type'] ?? 'package') ?>">

        <!-- Event Details -->
        <div class="panel mb-lg">
          <div class="panel-header"><h3>🎉 Event Details</h3></div>
          <div class="panel-body">
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label" for="event_type">Event Type *</label>
                <select name="event_type" id="event_type" class="form-select" required>
                  <option value="">— Select event type —</option>
                  <?php foreach (['Wedding','Birthday','Corporate','Graduation','Engagement','Anniversary','Eid Gathering','Other'] as $et): ?>
                    <option value="<?= e($et) ?>" <?= ($_POST['event_type']??'')===$et?'selected':'' ?>><?= e($et) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="event_date">Event Date *</label>
                <input type="date" name="event_date" id="event_date" class="form-input"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       value="<?= e($_POST['event_date'] ?? '') ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="event_location">Event Location</label>
              <input type="text" name="event_location" id="event_location" class="form-input"
                     placeholder="Venue name or full address"
                     value="<?= e($_POST['event_location'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="num_guests">Number of Guests *</label>
              <input type="number" name="num_guests" id="num_guests" class="form-input"
                     placeholder="e.g. 150" min="1" max="5000"
                     value="<?= e($_POST['num_guests'] ?? '') ?>" required>
            </div>
          </div>
        </div>

        <!-- Menu Selection Tabs -->
        <div class="panel mb-lg">
          <div class="panel-header"><h3>🍽️ Menu Selection</h3></div>
          <div class="panel-body">
            <div class="booking-tabs">
              <button type="button" class="booking-tab <?= ($_POST['booking_type']??'package')==='package'?'active':'' ?>"
                      onclick="switchTab('package')">
                📦 Choose a Package
              </button>
              <button type="button" class="booking-tab <?= ($_POST['booking_type']??'package')==='custom'?'active':'' ?>"
                      onclick="switchTab('custom')">
                🛒 Custom Menu Items
              </button>
            </div>

            <!-- Package tab -->
            <div id="tab-package" <?= ($_POST['booking_type']??'package')!=='package'?'style="display:none"':'' ?>>
              <?php if (empty($pkgs_list)): ?>
                <p class="text-muted" style="text-align:center;padding:2rem">No packages available right now.</p>
              <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:0.75rem">
                  <?php foreach ($pkgs_list as $pk):
                    $sel  = ($_POST['booking_type']??'package')==='package' && ($_POST['package_id']??'')==$pk['id'];
                    $img  = $pk['image_url'] ?: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&q=80'; ?>
                    <label class="pkg-radio-card <?= $sel?'selected':'' ?>"
                           id="pkg-card-<?= (int)$pk['id'] ?>">
                      <input type="radio" name="package_id"
                             value="<?= (int)$pk['id'] ?>"
                             data-price="<?= (float)$pk['price'] ?>"
                             <?= $sel?'checked':'' ?>
                             onchange="onPackageChange(this)">
                      <img src="<?= e($img) ?>" alt="<?= e($pk['name']) ?>"
                           onerror="this.src='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&q=80'">
                      <div class="pkg-info">
                        <div class="pkg-title"><?= e($pk['name']) ?></div>
                        <div class="pkg-price">Rs. <?= number_format($pk['price'],0) ?> / guest</div>
                        <div class="pkg-cap">👥 <?= (int)$pk['min_guests'] ?> – <?= (int)$pk['max_guests'] ?> guests</div>
                        <div class="pkg-desc"><?= e($pk['description'] ?: '') ?></div>
                      </div>
                    </label>
                  <?php endforeach; ?>
                </div>
                <div class="form-hint" style="margin-top:var(--space-sm)">
                  Total = Package price per guest × Number of guests
                </div>
              <?php endif; ?>
            </div>

            <!-- Custom items tab -->
            <div id="tab-custom" <?= ($_POST['booking_type']??'package')!=='custom'?'style="display:none"':'' ?>>
              <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:var(--space-md)">
                Browse our menu and add as many items as you like. Set the quantity for each item.
              </p>
              <?php foreach ($menu_by_cat as $cat => $items): ?>
                <div class="cat-header"><?= e($cat) ?></div>
                <div class="items-grid" style="margin-bottom:var(--space-md)">
                  <?php foreach ($items as $mi):
                    $prev = (int)($_POST['items'][$mi['id']] ?? 0);
                    $img  = $mi['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=70'; ?>
                    <div class="menu-item-row <?= $prev>0?'has-qty':'' ?>"
                         id="row-<?= (int)$mi['id'] ?>">
                      <img src="<?= e($img) ?>" alt="<?= e($mi['name']) ?>"
                           loading="lazy"
                           onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=70'">
                      <div class="item-info">
                        <div class="i-name"><?= e($mi['name']) ?></div>
                        <div class="i-price">Rs. <?= number_format($mi['price'],0) ?></div>
                      </div>
                      <div class="qty-wrap">
                        <button type="button" class="qty-btn"
                                onclick="adjustQty(<?= (int)$mi['id'] ?>, -1)">−</button>
                        <input type="number"
                               class="qty-num"
                               name="items[<?= (int)$mi['id'] ?>]"
                               id="qty-<?= (int)$mi['id'] ?>"
                               value="<?= $prev ?>"
                               min="0" max="999"
                               data-price="<?= (float)$mi['price'] ?>"
                               data-id="<?= (int)$mi['id'] ?>"
                               onchange="onQtyChange(this)"
                               oninput="onQtyChange(this)">
                        <button type="button" class="qty-btn"
                                onclick="adjustQty(<?= (int)$mi['id'] ?>, 1)">+</button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>

          </div>
        </div>

        <!-- Notes -->
        <div class="panel mb-lg">
          <div class="panel-header"><h3>📝 Special Notes</h3></div>
          <div class="panel-body">
            <div class="form-group">
              <label class="form-label" for="special_notes">Requirements / Preferences</label>
              <textarea name="special_notes" id="special_notes" class="form-textarea"
                        placeholder="Dietary requirements, decoration preferences, setup notes…"><?= e($_POST['special_notes'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-gold btn-full">
          <i class="fas fa-calendar-check"></i> Submit Booking Request
        </button>
      </form>

      <!-- Sidebar -->
      <div class="booking-sidebar-sticky">
        <div class="total-preview">
          <div class="tp-label">Estimated Total</div>
          <div class="tp-amount" id="tp_amount">—</div>
          <div class="tp-note"  id="tp_note">Select a package or add items</div>
        </div>

        <div class="panel">
          <div class="panel-header"><h3>Your Selection</h3></div>
          <div class="panel-body" id="selection-summary"
               style="font-size:0.85rem;color:var(--text-muted);min-height:60px">
            Nothing selected yet.
          </div>
        </div>

        <div class="panel">
          <div class="panel-header"><h3>Why Book With Us?</h3></div>
          <div class="panel-body">
            <div class="perks-list">
              <?php foreach ([
                ['🍽️','Premium quality food'],
                ['👨‍🍳','Expert chefs on site'],
                ['🎪','Full event setup included'],
                ['📞','24/7 customer support'],
                ['✅','Flexible menu options'],
              ] as $p): ?>
                <div class="perk-item">
                  <div class="perk-icon"><?= $p[0] ?></div>
                  <span><?= e($p[1]) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function switchTab(type) {
  document.getElementById('booking_type').value = type;
  document.getElementById('tab-package').style.display = type === 'package' ? '' : 'none';
  document.getElementById('tab-custom').style.display  = type === 'custom'  ? '' : 'none';
  document.querySelectorAll('.booking-tab').forEach((t, i) => {
    t.classList.toggle('active', (i===0&&type==='package')||(i===1&&type==='custom'));
  });
  updateTotal();
}

function onPackageChange(radio) {
  document.querySelectorAll('.pkg-radio-card').forEach(c => c.classList.remove('selected'));
  radio.closest('.pkg-radio-card').classList.add('selected');
  updateTotal();
}

function adjustQty(id, delta) {
  const input = document.getElementById('qty-' + id);
  let val = (parseInt(input.value) || 0) + delta;
  if (val < 0) val = 0;
  input.value = val;
  onQtyChange(input);
}

function onQtyChange(input) {
  const id  = input.dataset.id;
  const row = document.getElementById('row-' + id);
  if (row) row.classList.toggle('has-qty', (parseInt(input.value)||0) > 0);
  updateTotal();
}

function updateTotal() {
  const type   = document.getElementById('booking_type').value;
  const guests = parseInt(document.getElementById('num_guests').value) || 0;
  let   total  = 0;
  let   note   = '';
  let   summaryLines = [];

  if (type === 'package') {
    const checked = document.querySelector('input[name="package_id"]:checked');
    if (checked) {
      const pname = checked.closest('.pkg-radio-card').querySelector('.pkg-title').textContent;
      const price = parseFloat(checked.dataset.price);
      if (guests > 0) {
        total = price * guests;
        note  = guests + ' guests × Rs. ' + price.toLocaleString() + '/guest';
        summaryLines.push('<strong>' + pname + '</strong>');
        summaryLines.push(guests + ' guests @ Rs. ' + price.toLocaleString());
      } else {
        note = 'Enter number of guests';
        summaryLines.push('<strong>' + pname + '</strong>');
      }
    } else {
      note = 'Select a package above';
    }
  } else {
    const inputs = document.querySelectorAll('.qty-num');
    inputs.forEach(inp => {
      const qty   = parseInt(inp.value) || 0;
      const price = parseFloat(inp.dataset.price) || 0;
      if (qty > 0) {
        total += qty * price;
        const nm = inp.closest('.menu-item-row').querySelector('.i-name').textContent;
        summaryLines.push(nm + ' ×' + qty + ' = Rs. ' + (qty*price).toLocaleString());
      }
    });
    note = summaryLines.length > 0 ? summaryLines.length + ' item(s) selected' : 'Add items from the list';
  }

  document.getElementById('tp_amount').textContent = total > 0 ? 'Rs. ' + total.toLocaleString() : '—';
  document.getElementById('tp_note').textContent   = note;
  document.getElementById('selection-summary').innerHTML =
    summaryLines.length > 0
      ? summaryLines.map(l => '<div style="padding:3px 0;border-bottom:1px solid var(--border)">' + l + '</div>').join('')
      : '<span style="color:var(--text-subtle)">Nothing selected yet.</span>';
}

document.getElementById('num_guests').addEventListener('input', updateTotal);
document.addEventListener('DOMContentLoaded', updateTotal);
</script>

<?php require_once '../includes/footer.php'; ?>
