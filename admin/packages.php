<?php
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php');
}
$pageTitle   = 'Packages';
$currentPage = 'packages';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid   = (int)($_POST['pkg_id']      ?? 0);
    $name  = trim($_POST['name']         ?? '');
    $desc  = trim($_POST['description']  ?? '');
    $price = (float)($_POST['price']     ?? 0);
    $min_g = (int)($_POST['min_guests']  ?? 1);
    $max_g = (int)($_POST['max_guests']  ?? 500);
    $img   = trim($_POST['image_url']    ?? '');
    $avail = isset($_POST['available'])  ? 1 : 0;

    if ($pid) {
        // UPDATE: name(s) desc(s) price(d) min(i) max(i) img(s) avail(i) id(i) = ssdiisii
        $stmt = $conn->prepare("UPDATE packages SET name=?,description=?,price=?,min_guests=?,max_guests=?,image_url=?,available=? WHERE id=?");
        $stmt->bind_param('ssdiisii', $name, $desc, $price, $min_g, $max_g, $img, $avail, $pid);
    } else {
        // INSERT: name(s) desc(s) price(d) min(i) max(i) img(s) avail(i) = ssdiisi
        $stmt = $conn->prepare("INSERT INTO packages (name,description,price,min_guests,max_guests,image_url,available) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('ssdiisi', $name, $desc, $price, $min_g, $max_g, $img, $avail);
    }
    $stmt->execute();
    $stmt->close();
    setFlash('success', $pid ? 'Package updated.' : 'Package added.');
    redirect('packages.php');
}

if (isset($_GET['delete'])) {
    $did  = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM packages WHERE id=?");
    $stmt->bind_param('i', $did);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Package deleted.');
    redirect('packages.php');
}

$pkgs  = $conn->query("SELECT * FROM packages ORDER BY price ASC");
$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">📦 Catering Packages</span>
    </div>
    <div class="header-right">
      <button class="btn-gold btn-sm" data-modal-open="modal-add-pkg">
        <i class="fas fa-plus"></i> Add Package
      </button>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i><span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($pkgs->num_rows === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h3>No Packages Yet</h3>
        <p>Create your first catering package.</p>
        <button class="btn-gold" data-modal-open="modal-add-pkg"><i class="fas fa-plus"></i> Add Package</button>
      </div>
    <?php else: ?>
      <div class="grid-3">
        <?php while ($p = $pkgs->fetch_assoc()):
          $img = $p['image_url'] ?: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80'; ?>
          <div class="pkg-card reveal">
            <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                 onerror="this.src='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80'">
            <div class="pkg-body">
              <div class="pkg-name"><?= e($p['name']) ?></div>
              <div class="pkg-desc"><?= e($p['description'] ?: 'No description.') ?></div>
              <div class="pkg-price">Rs. <?= number_format($p['price'],0) ?> <span>/ per guest</span></div>
              <div class="d-flex align-center gap-sm mb-md" style="font-size:0.82rem;color:var(--text-muted)">
                <span>👥 <?= (int)$p['min_guests'] ?>–<?= (int)$p['max_guests'] ?> guests</span>
                <span class="badge-status <?= $p['available'] ? 'confirmed' : 'cancelled' ?>"><?= $p['available'] ? 'Active' : 'Inactive' ?></span>
              </div>
              <div class="d-flex gap-sm">
                <button class="btn-outline-gold btn-sm" style="flex:1" data-modal-open="modal-edit-pkg-<?= (int)$p['id'] ?>">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <a href="?delete=<?= (int)$p['id'] ?>" class="btn-danger"
                   data-confirm="Delete '<?= e($p['name']) ?>'?">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </div>
          </div>

          <div class="modal-overlay" id="modal-edit-pkg-<?= (int)$p['id'] ?>">
            <div class="modal-box">
              <div class="modal-header">
                <h3>Edit Package</h3>
                <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
              </div>
              <form method="POST" action="">
                <div class="modal-body">
                  <input type="hidden" name="pkg_id" value="<?= (int)$p['id'] ?>">
                  <div class="form-group">
                    <label class="form-label">Package Name *</label>
                    <input type="text" name="name" class="form-input" value="<?= e($p['name']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea"><?= e($p['description'] ?? '') ?></textarea>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Price per Guest (Rs.) *</label>
                    <input type="number" name="price" class="form-input" value="<?= (float)$p['price'] ?>" step="0.01" min="0" required>
                  </div>
                  <div class="grid-2">
                    <div class="form-group">
                      <label class="form-label">Min Guests</label>
                      <input type="number" name="min_guests" class="form-input" value="<?= (int)$p['min_guests'] ?>" min="1">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Max Guests</label>
                      <input type="number" name="max_guests" class="form-input" value="<?= (int)$p['max_guests'] ?>" min="1">
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Image URL</label>
                    <input type="url" name="image_url" class="form-input" value="<?= e($p['image_url'] ?? '') ?>" placeholder="https://…">
                  </div>
                  <label class="d-flex align-center gap-sm" style="cursor:pointer;font-size:0.9rem">
                    <input type="checkbox" name="available" <?= $p['available'] ? 'checked' : '' ?>>
                    Mark as Active
                  </label>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                  <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Save</button>
                </div>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="modal-overlay" id="modal-add-pkg">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Add New Package</h3>
      <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Package Name *</label>
          <input type="text" name="name" class="form-input" placeholder="e.g. Gold Package" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-textarea" placeholder="What's included…"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Price per Guest (Rs.) *</label>
          <input type="number" name="price" class="form-input" placeholder="1500" step="0.01" min="0" required>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Min Guests</label>
            <input type="number" name="min_guests" class="form-input" value="50" min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Max Guests</label>
            <input type="number" name="max_guests" class="form-input" value="500" min="1">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Image URL</label>
          <input type="url" name="image_url" class="form-input" placeholder="https://…">
        </div>
        <label class="d-flex align-center gap-sm" style="cursor:pointer;font-size:0.9rem">
          <input type="checkbox" name="available" checked>
          Mark as Active
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn-gold"><i class="fas fa-plus"></i> Add Package</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
