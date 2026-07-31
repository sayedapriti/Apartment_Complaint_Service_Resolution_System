<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/layout.php';
$user = requireLogin('admin');
$db   = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/admin/complaints.php'); exit; }

$stmt = $db->prepare("
    SELECT c.complaint_id AS id, c.complaint_title AS title, c.complaint_description AS description,
           c.complaint_status AS status, c.submitted_at AS created_at,
           u.full_name AS tenant_name, u.email AS tenant_email, u.phone AS tenant_phone, rp.apartment_number AS apartment,
           c.category_name AS category_name,
           s.full_name AS staff_name, s.email AS staff_email,
           c.assigned_staff_id AS assigned_staff_id,
           'CMP-' as ticket_no,
           'medium' as priority,
           '' as notes
    FROM complaints c
    JOIN resident_profiles rp ON c.resident_id=rp.resident_id
    JOIN users u ON rp.user_id=u.user_id
    LEFT JOIN service_staff_profiles ssp ON c.assigned_staff_id=ssp.staff_id
    LEFT JOIN users s ON ssp.user_id=s.user_id
    WHERE c.complaint_id=?
");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { header('Location: ' . APP_URL . '/admin/complaints.php'); exit; }
if (isset($c['id'])) {
    $c['ticket_no'] = 'CMP-' . str_pad($c['id'], 4, '0', STR_PAD_LEFT);
}

// History
$history = $db->prepare("
    SELECT cpu.update_id AS id, cpu.complaint_id, cpu.status_id AS new_status, NULL AS old_status,
           cpu.progress_note AS remark, cpu.updated_at AS created_at,
           u.full_name AS changed_by_name, u.role AS changed_by_role
    FROM complaint_progress_updates cpu
    JOIN users u ON cpu.updated_by=u.user_id
    WHERE cpu.complaint_id=?
    ORDER BY cpu.updated_at ASC
");
$history->execute([$id]);
$historyRows = $history->fetchAll();

// Staff list for assign
$staffList = $db->query("
    SELECT ssp.staff_id AS id, u.full_name AS name
    FROM service_staff_profiles ssp
    JOIN users u ON ssp.user_id = u.user_id
    ORDER BY u.full_name
")->fetchAll();

// Handle admin notes save
$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    if ($_POST['action']==='save_notes') {
        $notes = sanitize($_POST['notes'] ?? '');
        $msg = 'Notes saved.';
        $c['notes'] = $notes;
    }
    if ($_POST['action']==='update_priority') {
        $pri = $_POST['priority'] ?? 'medium';
        $c['priority'] = $pri;
        header('Location: ' . APP_URL . '/admin/complaint_detail.php?id=' . $id . '&saved=1');
        exit;
    }
}

renderPageStart('Complaint Detail', 'complaints');
?>

<div style="margin-bottom:20px;">
  <a href="<?= APP_URL ?>/admin/complaints.php" class="btn btn-ghost btn-sm">
    <i data-lucide="arrow-left" width="14" height="14"></i> Back to All Complaints
  </a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><i data-lucide="check-circle" width="16" height="16"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success"><i data-lucide="check-circle" width="16" height="16"></i> Changes saved successfully.</div><?php endif; ?>

<!-- Header row -->
<div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
  <div style="flex:1;min-width:0;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
      <span class="ticket-no" style="font-size:0.92rem;"><?= $c['ticket_no'] ?></span>
      <span class="badge <?= statusBadgeClass($c['status']) ?>"><?= str_replace('_',' ',$c['status']) ?></span>
      <span class="<?= priorityBadgeClass($c['priority']) ?>"><?= $c['priority'] ?></span>
    </div>
    <h1 style="font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($c['title']) ?></h1>
    <p style="color:var(--text-muted);font-size:0.85rem;">Submitted <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></p>
  </div>
  <div style="display:flex;gap:8px;">
    <button class="btn btn-primary" onclick="openAssignModal()">
      <i data-lucide="user-check" width="16" height="16"></i> Assign Staff
    </button>
    <button class="btn btn-ghost" onclick="promptStatusChange(<?= $id ?>, '<?= $c['status'] ?>')">
      <i data-lucide="refresh-cw" width="16" height="16"></i> Change Status
    </button>
  </div>
</div>

<div class="detail-grid">
  <!-- LEFT COLUMN -->
  <div style="display:flex;flex-direction:column;gap:20px;">
    <!-- Description -->
    <div class="card">
      <div class="card-header"><span class="card-title">Complaint Description</span></div>
      <div style="background:rgba(255,255,255,0.03);border-radius:10px;padding:18px;line-height:1.7;font-size:0.9rem;color:var(--text-secondary);">
        <?= nl2br(htmlspecialchars($c['description'])) ?>
      </div>
    </div>

    <!-- Admin Notes -->
    <div class="card">
      <div class="card-header"><span class="card-title">Internal Notes (Admin Only)</span></div>
      <form method="post">
        <input type="hidden" name="action" value="save_notes">
        <textarea name="notes" class="form-control" rows="4"
                  placeholder="Add internal notes visible only to admin and staff…"><?= htmlspecialchars($c['notes'] ?? '') ?></textarea>
        <div style="margin-top:12px;text-align:right;">
          <button class="btn btn-primary btn-sm" type="submit">
            <i data-lucide="save" width="14" height="14"></i> Save Notes
          </button>
        </div>
      </form>
    </div>

    <!-- History Timeline -->
    <div class="card">
      <div class="card-header"><span class="card-title">Activity Timeline</span></div>
      <?php if (empty($historyRows)): ?>
        <p class="text-muted text-sm">No activity recorded yet.</p>
      <?php else: ?>
      <ul class="timeline">
        <?php foreach ($historyRows as $h): ?>
        <li class="timeline-item">
          <div class="timeline-date"><?= date('d M Y, h:i A', strtotime($h['created_at'])) ?> &middot; <?= htmlspecialchars($h['changed_by_name']) ?></div>
          <div class="timeline-text"><?= htmlspecialchars($h['remark'] ?? 'Status changed') ?></div>
          <div class="timeline-badge">
            <?php if ($h['old_status']): ?>
              <span class="badge <?= statusBadgeClass($h['old_status']) ?>"><?= str_replace('_',' ',$h['old_status']) ?></span>
              &rarr;
            <?php endif; ?>
            <span class="badge <?= statusBadgeClass($h['new_status']) ?>"><?= str_replace('_',' ',$h['new_status']) ?></span>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- RIGHT COLUMN -->
  <div style="display:flex;flex-direction:column;gap:20px;">
    <!-- Meta -->
    <div class="card">
      <div class="card-header"><span class="card-title">Details</span></div>
      <div class="detail-meta">
        <div class="meta-item">
          <div class="meta-label">Category</div>
          <div class="meta-value"><?= htmlspecialchars($c['category_name']) ?></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Priority</div>
          <div class="meta-value"><span class="<?= priorityBadgeClass($c['priority']) ?>"><?= $c['priority'] ?></span></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Status</div>
          <div class="meta-value"><span class="badge <?= statusBadgeClass($c['status']) ?>"><?= str_replace('_',' ',$c['status']) ?></span></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Resolved</div>
          <div class="meta-value"><?= $c['resolved_at'] ? date('d M Y', strtotime($c['resolved_at'])) : '—' ?></div>
        </div>
      </div>

      <!-- Change priority inline -->
      <div style="margin-top:16px;">
        <form method="post" style="display:flex;gap:8px;align-items:center;">
          <input type="hidden" name="action" value="update_priority">
          <select name="priority" class="filter-select" style="flex:1;">
            <?php foreach (['low','medium','high','urgent'] as $p): ?>
            <option value="<?= $p ?>" <?= $c['priority']===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-ghost btn-sm">Update</button>
        </form>
      </div>
    </div>

    <!-- Tenant Info -->
    <div class="card">
      <div class="card-header"><span class="card-title">Tenant</span></div>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--navy-600),var(--navy-500));display:grid;place-items:center;font-weight:700;font-size:1rem;color:var(--amber-400);">
          <?= strtoupper(substr($c['tenant_name'],0,1)) ?>
        </div>
        <div>
          <div style="font-weight:600;color:var(--text-primary);"><?= htmlspecialchars($c['tenant_name']) ?></div>
          <div style="font-size:0.78rem;color:var(--text-muted);">Apt <?= htmlspecialchars($c['apartment'] ?? '—') ?></div>
        </div>
      </div>
      <div class="meta-item" style="margin-bottom:8px;">
        <div class="meta-label">Email</div>
        <div class="meta-value text-sm"><?= htmlspecialchars($c['tenant_email']) ?></div>
      </div>
      <div class="meta-item">
        <div class="meta-label">Phone</div>
        <div class="meta-value text-sm"><?= htmlspecialchars($c['tenant_phone'] ?? '—') ?></div>
      </div>
    </div>

    <!-- Assigned Staff -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Assigned Staff</span>
        <button class="btn btn-ghost btn-sm" onclick="openAssignModal()">
          <i data-lucide="edit-2" width="14" height="14"></i> Change
        </button>
      </div>
      <?php if ($c['staff_name']): ?>
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--teal-400),var(--teal-300));display:grid;place-items:center;font-weight:700;font-size:0.9rem;color:var(--navy-950);">
          <?= strtoupper(substr($c['staff_name'],0,1)) ?>
        </div>
        <div>
          <div style="font-weight:600;color:var(--text-primary);"><?= htmlspecialchars($c['staff_name']) ?></div>
          <div style="font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars($c['staff_email'] ?? '') ?></div>
        </div>
      </div>
      <?php else: ?>
      <div class="empty-state" style="padding:20px 0;">
        <div style="font-size:28px;margin-bottom:8px;">👤</div>
        <div style="font-size:0.85rem;color:var(--text-muted);">Not yet assigned</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Assign Modal -->
<div class="modal-overlay" id="assign-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Assign Staff Member</span>
      <button class="modal-close" onclick="closeModal('assign-modal')">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Select Staff</label>
        <select class="form-control" id="assign_staff_id">
          <option value="">-- Choose Staff Member --</option>
          <?php foreach ($staffList as $st): ?>
          <option value="<?= $st['id'] ?>" <?= $c['assigned_staff_id']==$st['id']?'selected':'' ?>><?= htmlspecialchars($st['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('assign-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="doAssign()">
        <i data-lucide="user-check" width="16" height="16"></i> Assign
      </button>
    </div>
  </div>
</div>

<script>
function openAssignModal() { openModal('assign-modal'); }
function doAssign() {
  const sid = document.getElementById('assign_staff_id').value;
  if (!sid) { toast('Please select a staff member','error'); return; }
  assignStaff(<?= $id ?>, sid);
  closeModal('assign-modal');
}
</script>

<?php renderPageEnd(); ?>
