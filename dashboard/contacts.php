<?php
/**
 * Dashboard - Contacts Management (Rolodex View)
 * Main dashboard page for partnership contacts
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Contacts.php';
require_once __DIR__ . '/../includes/helpers.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
if (!$user) {
    flashMessage('error', 'Unable to load user data.');
    redirect('../auth/logout.php');
}

// Initialize Contacts class
$contactsManager = new Contacts($user['id']);

// Get filter parameters
$stageFilter = get('stage', 'all');
$searchQuery = get('search', '');
$sortBy = get('sort', 'created_at');
$sortOrder = get('order', 'DESC');

// Get user's contacts with filters
$contacts = $contactsManager->getUserContacts($user['id'], $stageFilter, $searchQuery, $sortBy, $sortOrder);
$contactCount = count($contacts);

// Get usage information
$usage = $contactsManager->getUserUsage($user['id']);

// Get counts by stage
$stageCounts = $contactsManager->getContactCountByStage($user['id']);

// Get all stages for filter tabs
$stages = Contacts::getStages();

// Page title
$pageTitle = 'Contacts';
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
            gap: 0.5rem;
            text-decoration: none;
        }

        .site-logo img {
            width: 32px;
            height: 32px;
            object-fit: contain;
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
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
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

        .export-btn {
            background: white;
            color: #374151;
            padding: 0.75rem 1.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .export-btn:hover {
            background: #f9fafb;
            border-color: #6366f1;
        }

        /* Search and Filters */
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .search-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .search-input-wrapper {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .sort-select {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9375rem;
            background: white;
            cursor: pointer;
            min-width: 150px;
        }

        .sort-select:focus {
            outline: none;
            border-color: #6366f1;
        }

        /* Stage Filter Pills */
        .stage-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .stage-pill {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stage-pill.all {
            background: #f3f4f6;
            color: #374151;
        }

        .stage-pill.all.active,
        .stage-pill.all:hover {
            background: #1f2937;
            color: white;
        }

        .stage-pill.gray { background: #f3f4f6; color: #374151; }
        .stage-pill.gray.active, .stage-pill.gray:hover { background: #6b7280; color: white; }

        .stage-pill.blue { background: #dbeafe; color: #1e40af; }
        .stage-pill.blue.active, .stage-pill.blue:hover { background: #3b82f6; color: white; }

        .stage-pill.yellow { background: #fef3c7; color: #92400e; }
        .stage-pill.yellow.active, .stage-pill.yellow:hover { background: #f59e0b; color: white; }

        .stage-pill.orange { background: #ffedd5; color: #c2410c; }
        .stage-pill.orange.active, .stage-pill.orange:hover { background: #f97316; color: white; }

        .stage-pill.green { background: #d1fae5; color: #065f46; }
        .stage-pill.green.active, .stage-pill.green:hover { background: #10b981; color: white; }

        .stage-pill.red { background: #fee2e2; color: #991b1b; }
        .stage-pill.red.active, .stage-pill.red:hover { background: #ef4444; color: white; }

        .stage-count {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        /* Usage Widget */
        .usage-widget {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .usage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .usage-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .usage-plan {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .usage-plan.free { background: #f3f4f6; color: #6b7280; }
        .usage-plan.pro { background: #dbeafe; color: #1e40af; }
        .usage-plan.enterprise { background: #f3e8ff; color: #6b21a8; }

        .usage-count {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .usage-count .limit {
            color: #6b7280;
            font-weight: 400;
        }

        .usage-progress {
            height: 6px;
            background: #f3f4f6;
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .usage-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transition: width 0.3s ease;
        }

        .usage-progress-bar.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .usage-progress-bar.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

        .usage-upgrade {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #6366f1;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8125rem;
            font-weight: 500;
            transition: background 0.2s;
        }

        .usage-upgrade:hover {
            background: #4f46e5;
        }

        /* Contact Cards Grid */
        .contacts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
        }

        .contact-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .contact-card:hover {
            border-color: #6366f1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .contact-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .contact-company {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .contact-title {
            font-size: 0.8125rem;
            color: #9ca3af;
        }

        /* Stage Badge */
        .stage-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            white-space: nowrap;
        }

        .stage-badge.identified { background: #f3f4f6; color: #374151; }
        .stage-badge.outreach_sent { background: #dbeafe; color: #1e40af; }
        .stage-badge.in_discussion { background: #fef3c7; color: #92400e; }
        .stage-badge.negotiating { background: #ffedd5; color: #c2410c; }
        .stage-badge.active_partner { background: #d1fae5; color: #065f46; }
        .stage-badge.churned { background: #fee2e2; color: #991b1b; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 6rem 2rem;
            background: white;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
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
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }

        .flash-message.dismissing {
            animation: slideOut 0.3s ease-in forwards;
        }

        .flash-message.success { border-left-color: #10b981; color: #065f46; }
        .flash-message.error { border-left-color: #ef4444; color: #991b1b; }
        .flash-message.info { border-left-color: #3b82f6; color: #1e40af; }

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
                align-items: stretch;
            }

            .header-actions {
                flex-direction: column;
            }

            .new-btn, .export-btn {
                width: 100%;
                justify-content: center;
            }

            .search-row {
                flex-direction: column;
            }

            .search-input-wrapper {
                min-width: 100%;
            }

            .contacts-grid {
                grid-template-columns: 1fr;
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
            <a href="<?php echo url('/dashboard/contacts.php'); ?>" class="site-logo">
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
            <h1 class="page-title">Contacts</h1>
            <div class="header-actions">
                <?php if ($contactCount > 0): ?>
                    <a href="<?php echo url('/dashboard/export.php'); ?>" class="export-btn">
                        <span>📥</span> Export CSV
                    </a>
                <?php endif; ?>
                <?php if ($usage['can_create']): ?>
                    <a href="<?php echo url('/dashboard/contact-new.php'); ?>" class="new-btn">
                        <span>+</span> New Contact
                    </a>
                <?php else: ?>
                    <a href="<?php echo url('/pricing.php'); ?>" class="new-btn" style="background: #f59e0b;">
                        <span>⚡</span> Upgrade to Add More
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Usage Widget -->
        <div class="usage-widget">
            <div class="usage-header">
                <div class="usage-title">Contact Usage</div>
                <div class="usage-plan <?php echo $usage['plan']; ?>">
                    <?php echo ucfirst($usage['plan']); ?> Plan
                </div>
            </div>
            
            <div class="usage-count">
                <?php echo $usage['current']; ?>
                <?php if ($usage['limit'] !== null): ?>
                    <span class="limit">/ <?php echo $usage['limit']; ?> contacts</span>
                <?php else: ?>
                    <span class="limit">contacts (unlimited)</span>
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
                        ⚡ Upgrade to add more contacts
                    </a>
                <?php elseif ($usage['percentage'] >= 70): ?>
                    <a href="<?php echo url('pricing.php'); ?>" class="usage-upgrade">
                        📈 Upgrade your plan
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Search and Filters -->
        <div class="filters-section">
            <form class="search-row" method="GET" action="">
                <div class="search-input-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Search by name, company, or email..." 
                           value="<?php echo escape($searchQuery); ?>">
                </div>
                <select name="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                    <option value="company" <?php echo $sortBy === 'company' ? 'selected' : ''; ?>>Company A-Z</option>
                    <option value="updated_at" <?php echo $sortBy === 'updated_at' ? 'selected' : ''; ?>>Recently Updated</option>
                </select>
                <?php if ($stageFilter !== 'all'): ?>
                    <input type="hidden" name="stage" value="<?php echo escape($stageFilter); ?>">
                <?php endif; ?>
            </form>
            
            <!-- Stage Filter Pills -->
            <div class="stage-filters">
                <?php
                    $totalCount = array_sum($stageCounts);
                    $baseUrl = '?' . http_build_query(array_filter(['search' => $searchQuery, 'sort' => $sortBy]));
                ?>
                <a href="<?php echo $baseUrl; ?>" 
                   class="stage-pill all <?php echo $stageFilter === 'all' ? 'active' : ''; ?>">
                    All <span class="stage-count">(<?php echo $totalCount; ?>)</span>
                </a>
                <?php foreach ($stages as $stageKey => $stageData): ?>
                    <?php $stageCount = $stageCounts[$stageKey] ?? 0; ?>
                    <a href="<?php echo $baseUrl . '&stage=' . $stageKey; ?>" 
                       class="stage-pill <?php echo $stageData['color']; ?> <?php echo $stageFilter === $stageKey ? 'active' : ''; ?>">
                        <?php echo escape($stageData['name']); ?>
                        <span class="stage-count">(<?php echo $stageCount; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($contactCount > 0): ?>
            <!-- Contacts Grid -->
            <div class="contacts-grid">
                <?php foreach ($contacts as $contact): ?>
                    <a href="<?php echo url('/dashboard/contact-view.php?id=' . $contact['id']); ?>" class="contact-card">
                        <div class="card-header">
                            <div>
                                <div class="contact-name"><?php echo escape($contact['name']); ?></div>
                                <?php if ($contact['company']): ?>
                                    <div class="contact-company"><?php echo escape($contact['company']); ?></div>
                                <?php endif; ?>
                                <?php if ($contact['title']): ?>
                                    <div class="contact-title"><?php echo escape($contact['title']); ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="stage-badge <?php echo escape($contact['stage']); ?>">
                                <?php echo escape($stages[$contact['stage']]['name'] ?? $contact['stage']); ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <?php if ($searchQuery || $stageFilter !== 'all'): ?>
                    <div class="empty-state-icon">🔍</div>
                    <div class="empty-state-title">No contacts found</div>
                    <div class="empty-state-text">
                        Try adjusting your search or filters to find what you're looking for.
                    </div>
                    <a href="<?php echo url('/dashboard/contacts.php'); ?>" class="empty-state-btn">
                        Clear Filters
                    </a>
                <?php else: ?>
                    <div class="empty-state-icon">👥</div>
                    <div class="empty-state-title">No contacts yet</div>
                    <div class="empty-state-text">
                        Start building your partnership rolodex.<br>
                        Add your first contact to get started.
                    </div>
                    <a href="<?php echo url('/dashboard/contact-new.php'); ?>" class="empty-state-btn">
                        <span style="font-size: 1.25rem;">+</span> Add Your First Contact
                    </a>
                <?php endif; ?>
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

