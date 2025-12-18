<?php
/**
 * Dashboard - Redirect to Contacts
 * Main dashboard page for authenticated users
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

// Redirect to contacts dashboard
redirect(url('/dashboard/contacts.php'));
exit;

// Legacy code below - keeping for reference
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Items.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
if (!$user) {
    flashMessage('error', 'Unable to load user data.');
    redirect('../auth/logout.php');
}

// Initialize Items class
$itemsManager = new Items($user['id']);

// Get user's items
$items = $itemsManager->getUserItems($user['id']);
$itemCount = count($items);

// Get usage information
$usage = $itemsManager->getUserUsage($user['id']);

// Page title
$pageTitle = 'Items';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #1f2937;
            min-height: 100vh;
        }

        /* Top Header */
        .top-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .site-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #6366f1;
            display: flex;
            align-items: center;
        }

        .site-logo img {
            width: 32px;
            height: 32px;
            object-fit: contain;
            gap: 0.5rem;
            text-decoration: none;
        }

        /* Account Dropdown */
        .account-wrapper {
            position: relative;
        }

        .account-trigger {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .account-trigger:hover {
            background: #f9fafb;
            border-color: #6366f1;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .avatar-fallback {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .account-text {
            font-weight: 500;
            color: #1f2937;
        }

        .dropdown-arrow {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            display: none;
        }

        .dropdown-menu.active {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: #1f2937;
            text-decoration: none;
            transition: background 0.2s;
            border-bottom: 1px solid #f3f4f6;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f9fafb;
        }

        .dropdown-item.danger {
            color: #ef4444;
        }

        .dropdown-item.danger:hover {
            background: #fef2f2;
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }

        .new-btn {
            background: #6366f1;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .new-btn:hover {
            background: #4f46e5;
        }

        /* Items List */
        .items-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .item-row {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .item-row:hover {
            border-color: #6366f1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .item-title {
            font-size: 1rem;
            font-weight: 500;
            color: #1f2937;
        }

        /* Hamburger Menu */
        .hamburger-menu {
            position: relative;
        }

        .hamburger-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            color: #6b7280;
            font-size: 1.25rem;
            transition: color 0.2s;
        }

        .hamburger-btn:hover {
            color: #6366f1;
        }

        .hamburger-dropdown {
            position: absolute;
            top: calc(100% + 0.25rem);
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            min-width: 150px;
            display: none;
            z-index: 10;
        }

        .hamburger-dropdown.active {
            display: block;
        }

        .hamburger-item {
            padding: 0.625rem 1rem;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.9375rem;
        }

        .hamburger-item:last-child {
            border-bottom: none;
        }

        .hamburger-item:hover {
            background: #f9fafb;
        }

        .hamburger-item.danger {
            color: #ef4444;
        }

        .hamburger-item.danger:hover {
            background: #fef2f2;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 6rem 2rem;
            background: white;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            margin-top: 2rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .empty-state-text {
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .empty-state-btn {
            background: #6366f1;
            color: white;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .empty-state-btn:hover {
            background: #4f46e5;
        }

        /* Usage Widget */
        .usage-widget {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
        }

        .usage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .usage-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .usage-plan {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .usage-plan.free {
            background: #f3f4f6;
            color: #6b7280;
        }

        .usage-plan.pro {
            background: #dbeafe;
            color: #1e40af;
        }

        .usage-plan.enterprise {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .usage-count {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .usage-count .limit {
            color: #6b7280;
            font-weight: 400;
        }

        .usage-progress {
            height: 8px;
            background: #f3f4f6;
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .usage-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .usage-progress-bar.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .usage-progress-bar.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .usage-upgrade {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: #6366f1;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.2s;
        }

        .usage-upgrade:hover {
            background: #4f46e5;
        }

        /* Flash Messages */
        .flash-messages {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }

        .flash-message {
            padding: 1rem 1.5rem;
            padding-right: 3rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: white;
            border-left: 4px solid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease-out;
            position: relative;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .flash-message.dismissing {
            animation: slideOut 0.3s ease-in forwards;
        }

        .flash-message.success {
            border-left-color: #10b981;
            color: #065f46;
        }

        .flash-message.error {
            border-left-color: #ef4444;
            color: #991b1b;
        }

        .flash-message.info {
            border-left-color: #3b82f6;
            color: #1e40af;
        }
        
        .flash-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: transparent;
            border: none;
            color: currentColor;
            opacity: 0.5;
            cursor: pointer;
            font-size: 1.25rem;
            line-height: 1;
            padding: 0.25rem;
            transition: opacity 0.2s;
        }
        
        .flash-close:hover {
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .new-btn {
                width: 100%;
                justify-content: center;
            }

            .item-row {
                padding: 1rem;
            }

            .item-title {
                font-size: 0.9375rem;
            }

            .empty-state {
                padding: 4rem 1.5rem;
            }

            .empty-state-icon {
                font-size: 3rem;
            }

            .empty-state-title {
                font-size: 1.25rem;
            }

            .empty-state-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <header class="top-header">
        <div class="header-content">
            <a href="<?php echo url('/dashboard/'); ?>" class="site-logo">
                <img src="<?php echo url('assets/images/logo-small.png'); ?>" alt="<?php echo SITE_NAME; ?>">
                <span><?php echo SITE_NAME; ?></span>
            </a>
            
            <!-- Account Dropdown -->
            <div class="account-wrapper">
                <div class="account-trigger" onclick="toggleDropdown()">
                    <div class="avatar">
                        <?php if ($user['avatar_url']): ?>
                            <img src="<?php echo escape($user['avatar_url']); ?>" 
                                 alt="<?php echo escape($user['full_name']); ?>"
                                 referrerpolicy="no-referrer"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="avatar-fallback" style="display: none;">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                        <?php else: ?>
                            <div class="avatar-fallback">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="account-text">Account</span>
                    <span class="dropdown-arrow">▼</span>
                </div>
                
                <div class="dropdown-menu" id="accountDropdown">
                    <a href="<?php echo url('/dashboard/profile.php'); ?>" class="dropdown-item">Profile & Billing</a>
                    <a href="<?php echo url('/pricing.php'); ?>" class="dropdown-item">Pricing</a>
                    <a href="<?php echo url('/auth/logout.php'); ?>" class="dropdown-item danger">Log Out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (hasFlashMessages()): ?>
        <div class="flash-messages">
            <?php foreach (getFlashMessages() as $flash): ?>
                <div class="flash-message <?php echo escape($flash['type']); ?>">
                    <?php echo escape($flash['message']); ?>
                    <button class="flash-close" onclick="dismissFlash(this)" aria-label="Close">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Items</h1>
            <?php if ($usage['can_create']): ?>
                <a href="<?php echo url('/dashboard/item-new.php'); ?>" class="new-btn">
                    <span>+</span> New
                </a>
            <?php else: ?>
                <a href="<?php echo url('/pricing.php'); ?>" class="new-btn" style="background: #f59e0b;">
                    <span>⚡</span> Upgrade
                </a>
            <?php endif; ?>
        </div>

        <!-- Usage Widget -->
        <div class="usage-widget">
            <div class="usage-header">
                <div class="usage-title">Item Usage</div>
                <div class="usage-plan <?php echo $usage['plan']; ?>">
                    <?php echo ucfirst($usage['plan']); ?> Plan
                </div>
            </div>
            
            <div class="usage-count">
                <?php echo $usage['current']; ?>
                <?php if ($usage['limit'] !== null): ?>
                    <span class="limit">/ <?php echo $usage['limit']; ?> items</span>
                <?php else: ?>
                    <span class="limit">items (unlimited)</span>
                <?php endif; ?>
            </div>
            
            <?php if ($usage['limit'] !== null): ?>
                <div class="usage-progress">
                    <?php 
                        $progressClass = '';
                        if ($usage['percentage'] >= 90) {
                            $progressClass = 'danger';
                        } elseif ($usage['percentage'] >= 70) {
                            $progressClass = 'warning';
                        }
                    ?>
                    <div class="usage-progress-bar <?php echo $progressClass; ?>" 
                         style="width: <?php echo min($usage['percentage'], 100); ?>%"></div>
                </div>
                
                <?php if (!$usage['can_create']): ?>
                    <a href="<?php echo url('pricing.php'); ?>" class="usage-upgrade">
                        ⚡ Upgrade to create more items
                    </a>
                <?php elseif ($usage['percentage'] >= 70): ?>
                    <a href="<?php echo url('pricing.php'); ?>" class="usage-upgrade">
                        📈 Upgrade your plan
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($itemCount > 0): ?>
            <!-- Items List -->
            <div class="items-list">
                <?php foreach ($items as $item): ?>
                    <div class="item-row" onclick="editItem(<?php echo $item['id']; ?>)">
                        <div class="item-title"><?php echo escape($item['title']); ?></div>
                        <div class="hamburger-menu">
                            <button class="hamburger-btn" onclick="toggleHamburger(event, <?php echo $item['id']; ?>)">⋮</button>
                            <div class="hamburger-dropdown" id="hamburger-<?php echo $item['id']; ?>">
                                <div class="hamburger-item" onclick="editItem(event, <?php echo $item['id']; ?>)">Edit</div>
                                <div class="hamburger-item" onclick="duplicateItem(event, <?php echo $item['id']; ?>)">Duplicate</div>
                                <div class="hamburger-item danger" onclick="deleteItem(event, <?php echo $item['id']; ?>)">Delete</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <div class="empty-state-title">No items yet</div>
                <div class="empty-state-text">
                    Get started by creating your first item.<br>
                    Click the button below to begin.
                </div>
                <a href="<?php echo url('/dashboard/item-new.php'); ?>" class="empty-state-btn">
                    <span style="font-size: 1.25rem;">+</span> Create your first item
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Account Dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('accountDropdown');
            dropdown.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const accountWrapper = document.querySelector('.account-wrapper');
            if (!accountWrapper.contains(event.target)) {
                document.getElementById('accountDropdown').classList.remove('active');
            }
        });

        // Hamburger Menu
        function toggleHamburger(event, itemId) {
            event.stopPropagation(); // Prevent item click
            
            // Close all other hamburger menus
            document.querySelectorAll('.hamburger-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
            
            // Toggle this one
            const dropdown = document.getElementById(`hamburger-${itemId}`);
            dropdown.classList.toggle('active');
        }

        // Close hamburger menus when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.hamburger-menu')) {
                document.querySelectorAll('.hamburger-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });

        // Action Functions
        function editItem(eventOrId, itemId) {
            // Handle both (event, id) and (id) signatures for backwards compatibility
            if (typeof eventOrId === 'object' && eventOrId.stopPropagation) {
                eventOrId.stopPropagation();
                window.location.href = '<?php echo url('/dashboard/item-edit.php'); ?>?id=' + itemId;
            } else {
                // Called from row click - eventOrId is actually the itemId
                window.location.href = '<?php echo url('/dashboard/item-edit.php'); ?>?id=' + eventOrId;
            }
        }

        function duplicateItem(event, itemId) {
            event.stopPropagation();
            if (confirm('Duplicate this item?')) {
                window.location.href = '<?php echo url('/dashboard/item-actions.php'); ?>?action=duplicate&id=' + itemId;
            }
        }

        function deleteItem(event, itemId) {
            event.stopPropagation();
            if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                window.location.href = '<?php echo url('/dashboard/item-actions.php'); ?>?action=delete&id=' + itemId;
            }
        }
        
        // Flash message handling
        function dismissFlash(button) {
            const flashMessage = button.closest('.flash-message');
            flashMessage.classList.add('dismissing');
            setTimeout(() => {
                flashMessage.remove();
            }, 300);
        }
        
        // Auto-dismiss flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(function(message) {
                setTimeout(function() {
                    if (message.parentElement) {
                        message.classList.add('dismissing');
                        setTimeout(() => {
                            message.remove();
                        }, 300);
                    }
                }, 5000);
            });
        });
    </script>
</body>
</html>
