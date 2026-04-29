<?php
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php');
}
$pageTitle   = 'Menu Items';
$currentPage = 'menu';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid   = (int)($_POST['item_id']      ?? 0);
    $name  = trim($_POST['name']          ?? '');
    $desc  = trim($_POST['description']   ?? '');
    $price = (float)($_POST['price']      ?? 0);
    $cat   = trim($_POST['category']      ?? '');
    $img   = trim($_POST['image_url']     ?? '');
    $avail = isset($_POST['available']) ? 1 : 0;

    if ($mid) {
        $stmt = $conn->prepare("UPDATE menu_items SET name=?,description=?,price=?,category=?,image_url=?,available=? WHERE id=?");
        $stmt->bind_param('ssdssii', $name, $desc, $price, $cat, $img, $avail, $mid);
    } else {
        $stmt = $conn->prepare("INSERT INTO menu_items (name,description,price,category,image_url,available) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('ssdssi', $name, $desc, $price, $cat, $img, $avail);
    }
    $stmt->execute();
    $stmt->close();
    setFlash('success', $mid ? 'Item updated.' : 'Item added successfully.');
    redirect('menu.php');
}

if (isset($_GET['delete'])) {
    $did  = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id=?");
    $stmt->bind_param('i', $did);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Item deleted.');
    redirect('menu.php');
}

$filter_cat = trim($_GET['cat'] ?? '');
$search     = trim($_GET['q']   ?? '');

if ($search !== '' && $filter_cat !== '') {
    $s = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category=? AND (name LIKE ? OR description LIKE ?) ORDER BY category,name");
    $stmt->bind_param('sss', $filter_cat, $s, $s);
} elseif ($filter_cat !== '') {
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category=? ORDER BY name");
    $stmt->bind_param('s', $filter_cat);
} elseif ($search !== '') {
    $s = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE name LIKE ? OR description LIKE ? ORDER BY category,name");
    $stmt->bind_param('ss', $s, $s);
} else {
    $stmt = $conn->prepare("SELECT * FROM menu_items ORDER BY category,name");
}
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

$cats  = $conn->query("SELECT DISTINCT category FROM menu_items ORDER BY category");
$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">🍽️ Menu Items</span>
    </div>
    <div class="header-right">
      <button class="btn-gold btn-sm" data-modal-open="modal-add-item">
        <i class="fas fa-plus"></i> Add Item
      </button>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i><span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <form method="GET" action="" class="filter-bar">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" name="q" placeholder="Search menu…" value="<?= e($search) ?>">
      </div>
      <select name="cat" class="filter-select">
        <option value="">All Categories</option>
        <?php while ($c = $cats->fetch_assoc()): ?>
          <option value="<?= e($c['category']) ?>" <?= $filter_cat===$c['category'] ? 'selected' : '' ?>>
            <?= e($c['category']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <button type="submit" class="btn-gold btn-sm"><i class="fas fa-filter"></i> Filter</button>
      <a href="menu.php" class="btn-ghost btn-sm"><i class="fas fa-times"></i> Reset</a>
    </form>

    <div class="grid-3">
      <?php if ($items->num_rows === 0): ?>
        <div class="empty-state" style="grid-column:1/-1">
          <div class="empty-icon">🍽️</div>
          <h3>No Items Found</h3>
          <p>Try a different filter or add a new menu item.</p>
          <button class="btn-gold" data-modal-open="modal-add-item"><i class="fas fa-plus"></i> Add Item</button>
        </div>
      <?php else: while ($item = $items->fetch_assoc()): ?>
        <div class="card-catering reveal">
          <img class="card-img"
               src="<?= e($item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=70') ?>"
               alt="<?= e($item['name']) ?>"
               loading="lazy"
               onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=70'">
          <div class="card-body">
            <span class="card-badge"><?= e($item['category']) ?></span>
            <div class="card-title"><?= e($item['name']) ?></div>
            <div class="card-desc"><?= e($item['description'] ?: 'No description provided.') ?></div>
            <div class="card-footer">
              <div class="card-price">Rs. <?= number_format($item['price'],0) ?></div>
              <span class="badge-status <?= $item['available'] ? 'confirmed' : 'cancelled' ?>">
                <?= $item['available'] ? 'Available' : 'Unavailable' ?>
              </span>
            </div>
            <div class="d-flex gap-sm mt-md">
              <button class="btn-outline-gold btn-sm" style="flex:1"
                      data-modal-open="modal-edit-item-<?= (int)$item['id'] ?>">
                <i class="fas fa-edit"></i> Edit
              </button>
              <a href="?delete=<?= (int)$item['id'] ?>" class="btn-danger"
                 data-confirm="Delete '<?= e($item['name']) ?>'?">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </div>
        </div>

        <div class="modal-overlay" id="modal-edit-item-<?= (int)$item['id'] ?>">
          <div class="modal-box">
            <div class="modal-header">
              <h3>Edit — <?= e($item['name']) ?></h3>
              <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="">
              <div class="modal-body">
                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                <div class="form-group">
                  <label class="form-label">Name *</label>
                  <input type="text" name="name" class="form-input" value="<?= e($item['name']) ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Description</label>
                  <textarea name="description" class="form-textarea"><?= e($item['description'] ?? '') ?></textarea>
                </div>
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label">Price (Rs.) *</label>
                    <input type="number" name="price" class="form-input" value="<?= (float)$item['price'] ?>" step="0.01" min="0" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Category *</label>
                    <input type="text" name="category" class="form-input" value="<?= e($item['category']) ?>" required>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Image URL</label>
                  <input type="url" name="image_url" class="form-input" value="<?= e($item['image_url'] ?? '') ?>" placeholder="https://…">
                </div>
                <label class="d-flex align-center gap-sm" style="cursor:pointer;font-size:0.9rem">
                  <input type="checkbox" name="available" <?= $item['available'] ? 'checked' : '' ?>>
                  Mark as Available
                </label>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Save</button>
              </div>
            </form>
          </div>
        </div>
      <?php endwhile; endif; ?>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-add-item">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Add New Menu Item</h3>
      <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Name *</label>
          <input type="text" name="name" class="form-input" placeholder="e.g. Chicken BBQ" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-textarea" placeholder="Brief description…"></textarea>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Price (Rs.) *</label>
            <input type="number" name="price" class="form-input" placeholder="500" step="0.01" min="0" required>
          </div>
          <div class="form-group">
            <label class="form-label">Category *</label>
            <input type="text" name="category" class="form-input" placeholder="Main Course" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Image URL</label>
          <input type="url" name="image_url" class="form-input" placeholder="https://…">
          <div class="form-hint">Leave blank for a default food image.</div>
        </div>
        <label class="d-flex align-center gap-sm" style="cursor:pointer;font-size:0.9rem">
          <input type="checkbox" name="available" checked>
          Mark as Available
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn-gold"><i class="fas fa-plus"></i> Add Item</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
