<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized action.']);
}

$complaintId = isset($_POST['complaint_id']) ? (int)$_POST['complaint_id'] : 0;
$staffId     = isset($_POST['staff_id']) ? (int)$_POST['staff_id'] : 0;

if (!$complaintId || !$staffId) {
    jsonResponse(['success' => false, 'message' => 'Invalid parameters.']);
}

$db = getDB();

try {
    $db->beginTransaction();

    $assignerId = is_numeric($user['id']) ? (int)$user['id'] : 1;

    // Update complaints status to assigned and set assignment/progress details
    $stmt = $db->prepare("UPDATE complaints SET assigned_staff_id = ?, assigned_by = ?, assigned_at = NOW(), complaint_status = 'assigned', progress_note = 'Staff member assigned.', updated_by = ? WHERE complaint_id = ?");
    $stmt->execute([$staffId, $assignerId, $assignerId, $complaintId]);

    $db->commit();
    jsonResponse(['success' => true, 'message' => 'Staff assigned successfully.']);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
