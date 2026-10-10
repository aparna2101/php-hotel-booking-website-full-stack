<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); exit;
}
require_once '../config/database.php';
if (!$db_connected || !$pdo) {
    die('<p style="padding:40px;font-family:sans-serif;color:red;">Database not connected. Start MySQL in XAMPP.</p>');
}

$alert = ''; $alert_type = 'success';

// SAVE ROOM (ADD or EDIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'save_room') {
    $room_id     = intval($_POST['room_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $price       = intval($_POST['price'] ?? 0);
    $badge       = trim($_POST['badge'] ?? '');
    $capacity    = trim($_POST['capacity'] ?? '');
    $bed         = trim($_POST['bed'] ?? '');
    $size        = trim($_POST['size'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = trim($_POST['status'] ?? 'Available');
    $amenities_arr = array_filter(array_map('trim', explode(',', trim($_POST['amenities'] ?? ''))));
    $amenities_json = json_encode(array_values($amenities_arr));

    if (!$title || !$category || $price <= 0 || !$capacity || !$bed || !$description) {
        $alert = 'Please fill all required fields.'; $alert_type = 'error';
    } else {
        $image_path = trim($_POST['existing_image'] ?? 'assets/images/room1.jpg');
        if (!empty($_FILES['room_image']['name'])) {
            $upload_dir = '../uploads/rooms/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $alert = 'Invalid image type. Use JPG, PNG or WEBP.'; $alert_type = 'error';
            } elseif ($_FILES['room_image']['size'] > 3145728) {
                $alert = 'Image too large. Max 3MB.'; $alert_type = 'error';
            } else {
                $fn = 'room_' . time() . '_' . mt_rand(100,999) . '.' . $ext;
                if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_dir . $fn)) {
                    $old = trim($_POST['existing_image'] ?? '');
                    if ($old && strpos($old, 'uploads/rooms/') !== false) @unlink('../' . $old);
                    $image_path = 'uploads/rooms/' . $fn;
                } else { $alert = 'Upload failed. Check folder permissions.'; $alert_type = 'error'; }
            }
        }
        if (!$alert) {
            if ($room_id > 0) {
                $stmt = $pdo->prepare("UPDATE `rooms` SET `title`=?,`category`=?,`price`=?,`badge`=?,`image`=?,`capacity`=?,`bed`=?,`size`=?,`description`=?,`amenities`=?,`status`=? WHERE `id`=?");
                $stmt->execute([$title,$category,$price,$badge,$image_path,$capacity,$bed,$size,$description,$amenities_json,$status,$room_id]);
                $alert = 'Room <strong>'.htmlspecialchars($title).'</strong> updated!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO `rooms`(`title`,`category`,`price`,`badge`,`image`,`capacity`,`bed`,`size`,`description`,`amenities`,`status`) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$title,$category,$price,$badge,$image_path,$capacity,$bed,$size,$description,$amenities_json,$status]);
                $alert = 'Room <strong>'.htmlspecialchars($title).'</strong> added!';
            }
        }
    }
}
// DELETE ROOM
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $did = intval($_GET['delete']);
    $s = $pdo->prepare("SELECT `image` FROM `rooms` WHERE `id`=?"); $s->execute([$did]);
    $dr = $s->fetch();
    if ($dr && strpos($dr['image'], 'uploads/rooms/') !== false) @unlink('../'.$dr['image']);
    $pdo->prepare("DELETE FROM `rooms` WHERE `id`=?")->execute([$did]);
    header("Location: rooms.php?msg=deleted"); exit;
}
// TOGGLE STATUS
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    $ts = $pdo->prepare("SELECT `status` FROM `rooms` WHERE `id`=?"); $ts->execute([$tid]);
    $tr = $ts->fetch();
    $ns = ($tr['status'] === 'Available') ? 'Maintenance' : 'Available';
    $pdo->prepare("UPDATE `rooms` SET `status`=? WHERE `id`=?")->execute([$ns,$tid]);
    header("Location: rooms.php?msg=updated"); exit;
}

$rooms = $pdo->query("SELECT * FROM `rooms` ORDER BY `id` ASC")->fetchAll();
$edit_room = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM `rooms` WHERE `id`=?"); $s->execute([intval($_GET['edit'])]);
    $edit_room = $s->fetch();
    if ($edit_room) {
        $dec = json_decode($edit_room['amenities'], true);
        $edit_room['amenities_str'] = is_array($dec) ? implode(', ', $dec) : $edit_room['amenities'];
    }
}
$show_form = ($edit_room !== null || isset($_GET['add']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Rooms | HOTEL Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body{margin:0;}
.card{background:#FFF;border:1px solid #E2E8F0;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);}
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.fg3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;}
.fg4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:16px;}
.fg{margin-bottom:16px;}
.fl label{display:block;font-size:0.81rem;font-weight:600;color:var(--color-primary);margin-bottom:5px;}
.fl input,.fl select,.fl textarea{width:100%;padding:9px 12px;border:1.5px solid #CBD5E1;border-radius:var(--radius-xs);font-size:0.87rem;font-family:inherit;color:var(--color-primary);background:#FFF;transition:border-color 0.2s;box-sizing:border-box;}
.fl input:focus,.fl select:focus,.fl textarea:focus{outline:none;border-color:var(--color-gold);}
.fl textarea{min-height:80px;resize:vertical;}
.st-a{background:#DEF7EC;color:#03543F;} .st-b{background:#FEF3C7;color:#92400E;} .st-m{background:#FEE2E2;color:#991B1B;}
.stb{font-size:0.7rem;font-weight:700;padding:3px 9px;border-radius:20px;}
#imgPrev{width:100%;max-height:130px;object-fit:cover;border-radius:6px;border:1px solid #E2E8F0;display:none;margin-top:8px;}
@media(max-width:768px){.fg2,.fg3,.fg4{grid-template-columns:1fr !important;gap:12px;}.card{padding:18px 14px !important;}}
</style>
</head>
<body>
<div class="admin-layout">
<?php
  $admin_active_page = 'rooms';
  require_once 'nav.php';
?>

<main class="admin-main">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div>
      <h1 style="font-family:var(--font-serif);font-size:1.55rem;color:var(--color-primary);margin-bottom:3px;"><?php echo $edit_room ? '<i class="fa-solid fa-pen-to-square text-gold"></i> Edit Room' : 'Manage Rooms'; ?></h1>
      <p style="color:var(--color-text-muted);font-size:0.8rem;margin:0;">Add, edit, delete rooms. All changes reflect instantly on the website.</p>
    </div>
    <div style="display:flex;gap:8px;">
      <?php if($show_form): ?><a href="rooms.php" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a><?php endif; ?>
      <?php if(!$show_form): ?><a href="rooms.php?add=1" class="btn btn-gold btn-sm"><i class="fa-solid fa-plus"></i> Add New Room</a><?php endif; ?>
      <a href="../rooms.php" target="_blank" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-eye"></i> Preview</a>
    </div>
  </div>

  <!-- Alerts -->
  <?php if(isset($_GET['msg'])): ?>
    <div style="background:rgba(34,197,94,0.12);border:1px solid #22C55E;color:#166534;padding:10px 15px;border-radius:var(--radius-xs);margin-bottom:16px;font-size:0.86rem;">
      <i class="fa-solid fa-circle-check"></i> <?php echo $_GET['msg']==='deleted'?'Room deleted.':'Status updated.'; ?>
    </div>
  <?php endif; ?>
  <?php if($alert): ?>
    <div style="padding:10px 15px;border-radius:var(--radius-xs);margin-bottom:16px;font-size:0.86rem;<?php echo $alert_type==='error'?'background:#FEE2E2;border:1px solid #EF4444;color:#991B1B':'background:rgba(34,197,94,0.12);border:1px solid #22C55E;color:#166534'; ?>">
      <i class="fa-solid <?php echo $alert_type==='error'?'fa-circle-exclamation':'fa-circle-check'; ?>"></i> <?php echo $alert; ?>
    </div>
  <?php endif; ?>

  <!-- ADD/EDIT FORM -->
  <?php if($show_form): ?>
  <div class="card" style="padding:24px 28px;margin-bottom:24px;">
    <h2 style="font-family:var(--font-serif);font-size:1.1rem;color:var(--color-primary);margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #E2E8F0;">
      <i class="fa-solid <?php echo $edit_room?'fa-pen-to-square':'fa-plus-circle'; ?> text-gold"></i>
      <?php echo $edit_room?'Update Room Details':'Add New Room'; ?>
    </h2>
    <form method="POST" action="rooms.php" enctype="multipart/form-data">
      <input type="hidden" name="form_action" value="save_room">
      <input type="hidden" name="room_id" value="<?php echo $edit_room?$edit_room['id']:0; ?>">
      <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_room['image']??''); ?>">

      <div class="fg3 fg">
        <div class="fl"><label><i class="fa-solid fa-heading text-gold"></i> Room Title *</label>
          <input type="text" name="title" value="<?php echo htmlspecialchars($edit_room['title']??''); ?>" placeholder="e.g. Oceanfront Master Room" required></div>
        <div class="fl"><label><i class="fa-solid fa-tag text-gold"></i> Category *</label>
          <select name="category">
            <?php foreach(['Ocean View Room','Penthouse','Garden Villa','Deluxe Room','Premium Room','Family Room','Budget Room'] as $c): ?>
              <option value="<?php echo $c; ?>" <?php echo (($edit_room['category']??'') === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="fl"><label><i class="fa-solid fa-certificate text-gold"></i> Badge (optional)</label>
          <input type="text" name="badge" value="<?php echo htmlspecialchars($edit_room['badge']??''); ?>" placeholder="e.g. Most Popular"></div>
      </div>

      <div class="fg4 fg">
        <div class="fl"><label><i class="fa-solid fa-indian-rupee-sign text-gold"></i> Price/Night (&#8377;) *</label>
          <input type="number" name="price" value="<?php echo $edit_room['price']??''; ?>" placeholder="4999" min="100" required></div>
        <div class="fl"><label><i class="fa-solid fa-users text-gold"></i> Capacity *</label>
          <input type="text" name="capacity" value="<?php echo htmlspecialchars($edit_room['capacity']??''); ?>" placeholder="2 Adults, 1 Child" required></div>
        <div class="fl"><label><i class="fa-solid fa-bed text-gold"></i> Bed Type *</label>
          <input type="text" name="bed" value="<?php echo htmlspecialchars($edit_room['bed']??''); ?>" placeholder="1 King Bed" required></div>
        <div class="fl"><label><i class="fa-solid fa-expand text-gold"></i> Room Size</label>
          <input type="text" name="size" value="<?php echo htmlspecialchars($edit_room['size']??''); ?>" placeholder="650 sq.ft"></div>
      </div>

      <div class="fg fg2">
        <div class="fl"><label><i class="fa-solid fa-list-check text-gold"></i> Amenities <small style="color:var(--color-text-muted);font-weight:400;">(comma separated)</small></label>
          <input type="text" name="amenities" value="<?php echo htmlspecialchars($edit_room['amenities_str']??''); ?>" placeholder="Ocean View, Wi-Fi, Breakfast, Jacuzzi"></div>
        <div class="fl"><label><i class="fa-solid fa-toggle-on text-gold"></i> Status</label>
          <select name="status">
            <?php foreach(['Available','Booked','Maintenance'] as $st): ?>
              <option value="<?php echo $st; ?>" <?php echo ($edit_room['status']??'Available')===$st?'selected':''; ?>><?php echo $st; ?></option>
            <?php endforeach; ?>
          </select></div>
      </div>

      <div class="fl fg"><label><i class="fa-solid fa-align-left text-gold"></i> Description *</label>
        <textarea name="description" placeholder="Full room description..." required><?php echo htmlspecialchars($edit_room['description']??''); ?></textarea></div>

      <div class="fl fg"><label><i class="fa-solid fa-image text-gold"></i> Room Photo (JPG/PNG/WEBP, max 3MB)</label>
        <?php if(!empty($edit_room['image'])): ?>
          <div style="margin-bottom:8px;display:flex;align-items:center;gap:12px;">
            <img src="../<?php echo htmlspecialchars($edit_room['image']); ?>" style="height:55px;width:82px;object-fit:cover;border-radius:6px;border:1px solid #E2E8F0;" onerror="this.src='../assets/images/room1.jpg'">
            <span style="font-size:0.78rem;color:var(--color-text-muted);">Current photo. Upload new to replace.</span>
          </div>
        <?php endif; ?>
        <input type="file" name="room_image" accept="image/jpeg,image/png,image/webp" onchange="prevImg(this)" style="border:none;padding:0;">
        <img id="imgPrev" alt="Preview">
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-gold"><i class="fa-solid fa-floppy-disk"></i> <?php echo $edit_room?'Save Changes':'Add Room'; ?></button>
        <a href="rooms.php" class="btn btn-outline-dark"><i class="fa-solid fa-xmark"></i> Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- ROOMS TABLE -->
  <div class="card" style="overflow:hidden;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 22px;border-bottom:1px solid #E2E8F0;">
      <div>
        <h3 style="font-family:var(--font-serif);font-size:1.05rem;color:var(--color-primary);margin-bottom:2px;">
          All Rooms <span style="color:var(--color-gold);">(<?php echo count($rooms); ?>)</span>
        </h3>
        <p style="color:var(--color-text-muted);font-size:0.76rem;margin:0;">Changes reflect instantly on website — no code needed.</p>
      </div>
      <?php if(!$show_form): ?><a href="rooms.php?add=1" class="btn btn-gold btn-sm"><i class="fa-solid fa-plus"></i> Add Room</a><?php endif; ?>
    </div>

    <?php if(empty($rooms)): ?>
      <div style="padding:50px 20px;text-align:center;color:var(--color-text-muted);">
        <i class="fa-solid fa-bed" style="font-size:2rem;color:var(--color-gold);display:block;margin-bottom:10px;"></i>
        <p style="margin-bottom:14px;font-size:0.9rem;">No rooms added yet.</p>
        <a href="rooms.php?add=1" class="btn btn-gold btn-sm"><i class="fa-solid fa-plus"></i> Add First Room</a>
      </div>
    <?php else: ?>
      <div class="table-swipe-hint">
        <i class="fa-solid fa-arrows-left-right"></i> Swipe table to view full room details & edit/delete actions
      </div>
      <div class="admin-table-responsive">
        <table style="width:100%;min-width:620px;border-collapse:collapse;font-size:0.85rem;">
          <thead>
            <tr style="background:#F8FAFC;border-bottom:2px solid #E2E8F0;color:var(--color-text-muted);">
              <th style="padding:11px 14px;text-align:left;font-weight:600;">Photo</th>
              <th style="padding:11px 8px;text-align:left;font-weight:600;">Room Info</th>
              <th style="padding:11px 8px;font-weight:600;">Price</th>
              <th style="padding:11px 8px;font-weight:600;">Capacity</th>
              <th style="padding:11px 8px;font-weight:600;">Status</th>
              <th style="padding:11px 14px;text-align:right;font-weight:600;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rooms as $room): ?>
            <tr style="border-bottom:1px solid #F1F5F9;">
              <td style="padding:12px 14px;">
                <img src="../<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['title']); ?>"
                     style="width:66px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #E2E8F0;"
                     onerror="this.src='../assets/images/room1.jpg'">
              </td>
              <td style="padding:12px 8px;">
                <div style="font-weight:700;color:var(--color-primary);"><?php echo htmlspecialchars($room['title']); ?></div>
                <div style="font-size:0.74rem;color:var(--color-text-muted);margin-top:2px;">
                  <?php echo htmlspecialchars($room['category']); ?> &bull; <?php echo htmlspecialchars($room['bed']); ?>
                  <?php if($room['size']): ?>&bull; <?php echo htmlspecialchars($room['size']); ?><?php endif; ?>
                </div>
                <?php if($room['badge']): ?>
                  <span style="font-size:0.65rem;background:rgba(197,155,39,0.12);color:var(--color-gold);padding:2px 7px;border-radius:20px;font-weight:700;display:inline-block;margin-top:3px;"><?php echo htmlspecialchars($room['badge']); ?></span>
                <?php endif; ?>
              </td>
              <td style="padding:12px 8px;font-weight:700;color:var(--color-primary);white-space:nowrap;">&#8377;<?php echo number_format($room['price']); ?><span style="font-weight:400;color:var(--color-text-muted);font-size:0.74rem;">/night</span></td>
              <td style="padding:12px 8px;color:var(--color-text-muted);font-size:0.8rem;"><?php echo htmlspecialchars($room['capacity']); ?></td>
              <td style="padding:12px 8px;">
                <?php $sc=$room['status']==='Available'?'st-a':($room['status']==='Booked'?'st-b':'st-m'); ?>
                <span class="stb <?php echo $sc; ?>"><?php echo $room['status']; ?></span>
              </td>
              <td style="padding:12px 14px;text-align:right;white-space:nowrap;">
                <a href="rooms.php?edit=<?php echo $room['id']; ?>" class="btn btn-outline-dark btn-sm" style="padding:4px 9px;font-size:0.76rem;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                <a href="rooms.php?toggle=<?php echo $room['id']; ?>" class="btn btn-outline-dark btn-sm" style="padding:4px 9px;font-size:0.76rem;margin-left:4px;" title="Toggle Status"><i class="fa-solid fa-toggle-<?php echo $room['status']==='Available'?'on':'off'; ?>"></i></a>
                <a href="rooms.php?delete=<?php echo $room['id']; ?>" class="btn btn-outline-dark btn-sm" style="padding:4px 9px;font-size:0.76rem;color:#EF4444;border-color:#EF4444;margin-left:4px;" onclick="return confirm('Delete \'<?php echo addslashes($room['title']); ?>\'? Cannot be undone.')"><i class="fa-solid fa-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <p style="font-size:0.78rem;color:var(--color-text-muted);margin-top:12px;padding:11px 14px;background:rgba(197,155,39,0.07);border:1px solid var(--color-border-gold);border-radius:var(--radius-xs);">
    <i class="fa-solid fa-circle-info text-gold"></i>
    <strong>Tip:</strong> Rooms added here appear on the Homepage, Rooms page, and Booking form automatically.
  </p>

</main>
</div>
<script>
function prevImg(i) {
  const p = document.getElementById('imgPrev');
  if(i.files && i.files[0]){const r=new FileReader();r.onload=e=>{p.src=e.target.result;p.style.display='block'};r.readAsDataURL(i.files[0]);}
}
</script>
</body>
</html>
