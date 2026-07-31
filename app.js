// ============================================================
//  ResideEase – Main JS
// ============================================================

// ---------- Toast ----------
function toast(msg, type = 'info') {
  const c = document.getElementById('toast-container');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span class="toast-icon"></span><span>${msg}</span><button class="toast-close" onclick="this.parentElement.remove()">✕</button>`;
  c.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

// ---------- Sidebar toggle ----------
function toggleSidebar() {
  document.querySelector('.sidebar')?.classList.toggle('open');
}

// ---------- Modal helpers ----------
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
}
function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}
// Close on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// ---------- Confirm delete ----------
function confirmAction(msg, callback) {
  if (confirm(msg)) callback();
}

// ---------- Category selection ----------
function selectCategory(el, id) {
  document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  const inp = document.getElementById('category_id');
  if (inp) inp.value = id;
}

// ---------- AJAX helper ----------
async function apiPost(url, data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => fd.append(k, v));
  const r = await fetch(url, { method: 'POST', body: fd });
  return r.json();
}

// ---------- Status update (staff) ----------
async function updateStatus(complaintId, newStatus, remark = '') {
  try {
    const res = await apiPost('../api/update_status.php', {
      complaint_id: complaintId,
      status: newStatus,
      remark
    });
    if (res.success) {
      toast(res.message, 'success');
      setTimeout(() => location.reload(), 600);
    } else {
      const msg = res.message || 'Error occurred';
      if (!document.getElementById('toast-container')) alert(msg);
      else toast(msg, 'error');
    }
  } catch (e) {
    const msg = 'Network error: ' + e.message;
    if (!document.getElementById('toast-container')) alert(msg);
    else toast(msg, 'error');
  }
}

// ---------- Assign staff (admin) ----------
async function assignStaff(complaintId, staffId) {
  if (!staffId) return;
  try {
    const res = await apiPost('../api/assign_staff.php', {
      complaint_id: complaintId,
      staff_id: staffId
    });
    if (res.success) {
      toast(res.message, 'success');
      setTimeout(() => location.reload(), 600);
    } else {
      const msg = res.message || 'Error';
      if (!document.getElementById('toast-container')) alert(msg);
      else toast(msg, 'error');
    }
  } catch (e) {
    const msg = 'Network error: ' + e.message;
    if (!document.getElementById('toast-container')) alert(msg);
    else toast(msg, 'error');
  }
}

// ---------- Filter table ----------
function filterTable(inputId, tableId) {
  const q = document.getElementById(inputId)?.value.toLowerCase() || '';
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ---------- Status change prompt ----------
function promptStatusChange(complaintId, currentStatus) {
  const statuses = ['assigned','in_progress','resolved','closed'];
  const statusLabels = {
    assigned:'Assigned', in_progress:'In Progress',
    resolved:'Resolved', closed:'Closed'
  };
  const modal = document.getElementById('status-modal');
  if (!modal) return;
  document.getElementById('sc_complaint_id').value = complaintId;
  const sel = document.getElementById('sc_status');
  sel.innerHTML = '';
  statuses.forEach(s => {
    const o = document.createElement('option');
    o.value = s; o.textContent = statusLabels[s];
    if (s === currentStatus) o.selected = true;
    sel.appendChild(o);
  });
  document.getElementById('sc_remark').value = '';
  openModal('status-modal');
}

async function submitStatusChange() {
  const id     = document.getElementById('sc_complaint_id').value;
  const status = document.getElementById('sc_status').value;
  const remark = document.getElementById('sc_remark').value;
  closeModal('status-modal');
  await updateStatus(id, status, remark);
}

// ---------- Mark notifications read ----------
function markNotifsRead() {
  fetch('../api/mark_notifs.php', { method:'POST' });
  document.querySelectorAll('.notif-dot').forEach(d => d.remove());
}

// ---------- Animate stats on load ----------
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.stat-value[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count);
    let current = 0;
    const step = Math.ceil(target / 25);
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current;
      if (current >= target) clearInterval(timer);
    }, 30);
  });
});
