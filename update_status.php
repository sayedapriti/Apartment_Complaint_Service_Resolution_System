<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$user = currentUser();
if (!$user || !in_array($user['role'], ['admin', 'staff'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized action.']);
}

// Check POST parameters
$complaintId = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
$newStatus   = isset($_POST['status']) ? sanitize($_POST['status']) : '';
$remark      = isset($_POST['remark']) ? sanitize($_POST['remark']) : '';

if (!$complaintId || !$newStatus) {
    jsonResponse(['success' => false, 'message' => 'Invalid parameters.']);
}

// Validate status
$validStatuses = ['open', 'pending', 'assigned', 'in_progress', 'resolved', 'closed', 'rejected'];
if (!in_array($newStatus, $validStatuses)) {
    jsonResponse(['success' => false, 'message' => 'Invalid status value.']);
}

$db = getDB();

try {
    // If user is staff, verify they are assigned to this complaint
    if ($user['role'] === 'staff') {
        $sid = $user['profile_id'];
        $chk = $db->prepare("SELECT complaint_id FROM complaints WHERE complaint_id = ? AND assigned_staff_id = ?");
        $chk->execute([$complaintId, $sid]);
        if (!$chk->fetch()) {
            jsonResponse(['success' => false, 'message' => 'You are not assigned to this complaint.']);
        }
    }

    $db->beginTransaction();

    // 1. Update complaints status and progress note
    $updatedBy = is_numeric($user['id']) ? (int)$user['id'] : 1;
    $progNote = $remark ?: 'Status updated to ' . str_replace('_', ' ', $newStatus);
    
    $stmt = $db->prepare("UPDATE complaints SET complaint_status = ?, progress_note = ?, updated_by = ? WHERE complaint_id = ?");
    $stmt->execute([$newStatus, $progNote, $updatedBy, $complaintId]);

    // 3. If resolved, log in resolution_details
    if ($newStatus === 'resolved' && $user['role'] === 'staff') {
        $sid = $user['profile_id'];
        
        // Check if already resolved to avoid duplicate
        $chkRes = $db->prepare("SELECT resolution_id FROM resolution_details WHERE complaint_id = ?");
        $chkRes->execute([$complaintId]);
        if ($chkRes->fetch()) {
            $updRes = $db->prepare("UPDATE resolution_details SET staff_id = ?, resolution_description = ?, resolved_at = NOW() WHERE complaint_id = ?");
            $updRes->execute([$sid, $remark, $complaintId]);
        } else {
            $insRes = $db->prepare("INSERT INTO resolution_details (complaint_id, staff_id, resolution_description, resolved_at) VALUES (?, ?, ?, NOW())");
            $insRes->execute([$complaintId, $sid, $remark]);
        }
    }

    $db->commit();
    jsonResponse(['success' => true, 'message' => 'Complaint status updated successfully.']);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
