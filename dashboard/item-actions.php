<?php
/**
 * Item Actions Handler
 * Handles delete and duplicate actions for items
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Items.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
if (!$user) {
    flashMessage('error', 'Unable to load user data.');
    redirect('../auth/logout.php');
}

$itemsManager = new Items($user['id']);

// Get action and item ID from URL
$action = $_GET['action'] ?? '';
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$itemId || !$action) {
    flashMessage('error', 'Invalid request.');
    redirect('/dashboard/');
}

// Handle actions
switch ($action) {
    case 'delete':
        $success = $itemsManager->deleteItem($itemId, $user['id']);
        if ($success) {
            flashMessage('success', 'Item deleted successfully!');
        } else {
            flashMessage('error', 'Item not found or you do not have permission to delete it.');
        }
        break;
        
    case 'duplicate':
        // Check usage limits before duplicating
        $usage = $itemsManager->getUserUsage($user['id']);
        if (!$usage['can_create']) {
            flashMessage('error', 'You have reached your item limit. Please upgrade your plan to create more items.');
            redirect('/pricing.php');
        }
        
        $newItemId = $itemsManager->duplicateItem($itemId, $user['id']);
        if ($newItemId) {
            flashMessage('success', 'Item duplicated successfully!');
        } else {
            flashMessage('error', 'Item not found or you do not have permission to duplicate it.');
        }
        break;
        
    default:
        flashMessage('error', 'Invalid action.');
        break;
}

redirect('/dashboard/');

