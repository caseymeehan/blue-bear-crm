<?php
/**
 * Dashboard - Contact View/Edit
 * View and edit contact details, interactions, and social stats
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Contacts.php';
require_once __DIR__ . '/../includes/Interactions.php';
require_once __DIR__ . '/../includes/SocialStats.php';
require_once __DIR__ . '/../includes/helpers.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
if (!$user) {
    flashMessage('error', 'Unable to load user data.');
    redirect('../auth/logout.php');
}

// Get contact ID from URL
$contactId = get('id');
if (!$contactId) {
    flashMessage('error', 'No contact specified.');
    redirect('contacts.php');
}

// Initialize classes
$contactsManager = new Contacts($user['id']);
$interactionsManager = new Interactions();
$socialStatsManager = new SocialStats();

// Get contact
$contact = $contactsManager->getContact($contactId, $user['id']);
if (!$contact) {
    flashMessage('error', 'Contact not found.');
    redirect('contacts.php');
}

// Get interactions
$interactions = $interactionsManager->getContactInteractions($contactId, $user['id'], 'DESC');

// Get social stats
$socialStats = $socialStatsManager->getContactStats($contactId, $user['id']);
$socialStatsMap = [];
foreach ($socialStats as $stat) {
    $socialStatsMap[$stat['platform']] = $stat['followers'];
}

// Get all stages and platforms
$stages = Contacts::getStages();
$platforms = SocialStats::getPlatforms();

// Handle form submissions
$errors = [];
$success = '';

if (isPost()) {
    $action = post('action');
    
    // Validate CSRF token
    if (!validateCSRFToken(post('csrf_token'))) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        switch ($action) {
            case 'update_contact':
                // Update contact info
                $contactData = [
                    'name' => trim(post('name', '')),
                    'email' => trim(post('email', '')),
                    'phone' => trim(post('phone', '')),
                    'company' => trim(post('company', '')),
                    'title' => trim(post('title', '')),
                    'stage' => post('stage', $contact['stage']),
                    'linkedin_url' => trim(post('linkedin_url', '')),
                    'twitter_handle' => trim(post('twitter_handle', '')),
                    'youtube_channel' => trim(post('youtube_channel', '')),
                    'instagram_handle' => trim(post('instagram_handle', '')),
                    'tiktok_handle' => trim(post('tiktok_handle', ''))
                ];
                
                // Validate
                if (empty($contactData['name'])) {
                    $errors[] = 'Name is required.';
                }
                if (!empty($contactData['email']) && !isValidEmail($contactData['email'])) {
                    $errors[] = 'Please enter a valid email address.';
                }
                if (!Contacts::isValidStage($contactData['stage'])) {
                    $errors[] = 'Invalid stage selected.';
                }
                
                if (empty($errors)) {
                    if ($contactsManager->updateContact($contactId, $user['id'], $contactData)) {
                        $success = 'Contact updated successfully!';
                        // Refresh contact data
                        $contact = $contactsManager->getContact($contactId, $user['id']);
                    } else {
                        $errors[] = 'Failed to update contact.';
                    }
                }
                break;
                
            case 'add_interaction':
                $note = trim(post('note', ''));
                $title = trim(post('note_title', ''));
                if (empty($note)) {
                    $errors[] = 'Note cannot be empty.';
                } else {
                    if ($interactionsManager->createInteraction($contactId, $user['id'], $note, $title ?: null)) {
                        $success = 'Note added successfully!';
                        // Refresh interactions
                        $interactions = $interactionsManager->getContactInteractions($contactId, $user['id'], 'DESC');
                    } else {
                        $errors[] = 'Failed to add note.';
                    }
                }
                break;
                
            case 'delete_interaction':
                $interactionId = post('interaction_id');
                if ($interactionsManager->deleteInteraction($interactionId, $user['id'])) {
                    $success = 'Note deleted.';
                    // Refresh interactions
                    $interactions = $interactionsManager->getContactInteractions($contactId, $user['id'], 'DESC');
                } else {
                    $errors[] = 'Failed to delete note.';
                }
                break;
                
            case 'edit_interaction':
                $interactionId = post('interaction_id');
                $note = trim(post('note', ''));
                $title = trim(post('note_title', ''));
                if (empty($note)) {
                    $errors[] = 'Note cannot be empty.';
                } else {
                    if ($interactionsManager->updateInteraction($interactionId, $user['id'], $note, $title ?: null)) {
                        $success = 'Note updated successfully!';
                        // Refresh interactions
                        $interactions = $interactionsManager->getContactInteractions($contactId, $user['id'], 'DESC');
                    } else {
                        $errors[] = 'Failed to update note.';
                    }
                }
                break;
                
            case 'update_stats':
            case 'update_social':
                // Update social handles on contact
                $socialContactData = [
                    'name' => $contact['name'], // Keep existing required field
                    'linkedin_url' => trim(post('linkedin_url', '')),
                    'twitter_handle' => trim(post('twitter_handle', '')),
                    'youtube_channel' => trim(post('youtube_channel', '')),
                    'instagram_handle' => trim(post('instagram_handle', '')),
                    'tiktok_handle' => trim(post('tiktok_handle', ''))
                ];
                $contactsManager->updateContact($contactId, $user['id'], $socialContactData);
                // Refresh contact data
                $contact = $contactsManager->getContact($contactId, $user['id']);
                
                // Update stats
                $statsData = [];
                foreach ($platforms as $platformKey => $platformData) {
                    $value = post('stats_' . $platformKey, '');
                    $statsData[] = [
                        'platform' => $platformKey,
                        'followers' => $value === '' ? null : (int)$value
                    ];
                }
                $socialStatsManager->bulkUpdateStats($contactId, $user['id'], $statsData);
                $success = 'Social media updated!';
                // Refresh social stats
                $socialStats = $socialStatsManager->getContactStats($contactId, $user['id']);
                $socialStatsMap = [];
                foreach ($socialStats as $stat) {
                    $socialStatsMap[$stat['platform']] = $stat['followers'];
                }
                break;
        }
    }
}

// Page title
$pageTitle = $contact['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($pageTitle) . ' - ' . SITE_NAME; ?></title>
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

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9375rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #6366f1;
        }

        .delete-btn {
            color: #ef4444;
            background: none;
            border: 1px solid #fee2e2;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .delete-btn:hover {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }

        /* Stage Badge */
        .stage-badge {
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.375rem 1rem;
            border-radius: 9999px;
            white-space: nowrap;
        }

        .stage-badge.identified { background: #f3f4f6; color: #374151; }
        .stage-badge.outreach_sent { background: #dbeafe; color: #1e40af; }
        .stage-badge.in_discussion { background: #fef3c7; color: #92400e; }
        .stage-badge.negotiating { background: #ffedd5; color: #c2410c; }
        .stage-badge.active_partner { background: #d1fae5; color: #065f46; }
        .stage-badge.churned { background: #fee2e2; color: #991b1b; }

        /* Messages */
        .message {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fee2e2;
        }

        /* Layout Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Form Elements */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 0.375rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.9375rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-select {
            appearance: none;
            background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 0.875rem center;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        /* Buttons */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            font-size: 0.9375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
        }

        .btn-block {
            width: 100%;
        }

        /* Social Media Combined Section */
        .social-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .social-row {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .social-platform {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #374151;
        }

        .platform-name {
            font-size: 0.8125rem;
        }

        .social-inputs {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .social-handle-input {
            flex: 1;
            padding: 0.5rem 0.75rem;
            font-size: 0.8125rem;
        }

        .stat-icon {
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.5625rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .stat-icon.youtube { background: #ff0000; }
        .stat-icon.instagram { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }
        .stat-icon.twitter { background: #000000; }
        .stat-icon.linkedin { background: #0077b5; }
        .stat-icon.tiktok { background: #000000; }

        .stat-input {
            width: 90px;
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.8125rem;
            text-align: right;
            flex-shrink: 0;
        }

        .stat-input:focus {
            outline: none;
            border-color: #6366f1;
        }

        .total-reach {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
        }

        .reach-label {
            color: #6b7280;
        }

        .reach-value {
            font-weight: 600;
            color: #1f2937;
        }

        /* Interactions Timeline */
        .interactions-list {
            margin-top: 1rem;
        }

        .interaction-note {
            font-size: 0.9375rem;
            color: #1f2937;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .interaction-meta {
            margin-top: 0.75rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .interaction-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .interaction-edit,
        .interaction-delete {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .interaction-edit {
            color: #6366f1;
        }

        .interaction-delete {
            color: #ef4444;
        }

        .interaction-edit:hover,
        .interaction-delete:hover {
            opacity: 1;
        }

        .interaction-edit-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .interaction-edit-form .edit-title-input {
            margin-bottom: 0.5rem;
        }

        .interaction-edit-form .edit-note-input {
            min-height: 80px;
            margin-bottom: 0.75rem;
        }

        .edit-form-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
        }

        .add-note-form {
            margin-bottom: 1rem;
        }

        .add-note-form .note-title-input {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .add-note-form .form-textarea {
            min-height: 60px;
            margin-bottom: 0.75rem;
        }

        .interaction-item {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .interaction-item:last-child {
            margin-bottom: 0;
        }

        .interaction-item:hover {
            background: #f0f1f3;
        }

        .interaction-item.expanded {
            background: #f0f1f3;
        }

        .interaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .interaction-title {
            font-size: 0.9375rem;
            font-weight: 500;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            min-width: 0;
        }

        .interaction-toggle {
            font-size: 0.625rem;
            color: #9ca3af;
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }

        .interaction-item.expanded .interaction-toggle {
            transform: rotate(90deg);
        }

        .interaction-time {
            font-size: 0.75rem;
            color: #9ca3af;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .interaction-content {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
            animation: slideDown 0.2s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-interactions {
            text-align: center;
            padding: 2rem 1rem;
            color: #9ca3af;
            font-size: 0.9375rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .header-actions {
                flex-direction: column;
                gap: 0.5rem;
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
            
            <div class="header-actions">
                <a href="<?php echo url('/dashboard/contacts.php'); ?>" class="back-link">
                    ← Back to Contacts
                </a>
                <a href="<?php echo url('/dashboard/contact-actions.php?action=delete&id=' . $contact['id']); ?>" 
                   class="delete-btn"
                   onclick="return confirm('Are you sure you want to delete this contact? This action cannot be undone.');">
                    Delete Contact
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title"><?php echo escape($contact['name']); ?></h1>
                <p class="page-subtitle">
                    <?php if ($contact['company']): ?>
                        <?php echo escape($contact['company']); ?>
                        <?php if ($contact['title']): ?>
                            &bull; <?php echo escape($contact['title']); ?>
                        <?php endif; ?>
                    <?php elseif ($contact['title']): ?>
                        <?php echo escape($contact['title']); ?>
                    <?php else: ?>
                        Partnership Contact
                    <?php endif; ?>
                </p>
            </div>
            <span class="stage-badge <?php echo escape($contact['stage']); ?>">
                <?php echo escape($stages[$contact['stage']]['name'] ?? $contact['stage']); ?>
            </span>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="message success"><?php echo escape($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php echo escape(implode(' ', $errors)); ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Left Column - Contact Info -->
            <div class="left-column">
                <!-- Contact Details -->
                <form method="POST" class="card">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_contact">
                    
                    <h2 class="card-title">
                        Contact Details
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                    </h2>
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-input" 
                                   value="<?php echo escape($contact['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" 
                                   value="<?php echo escape($contact['email']); ?>"
                                   placeholder="email@company.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-input" 
                                   value="<?php echo escape($contact['phone']); ?>"
                                   placeholder="+1 (555) 000-0000">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Company</label>
                            <input type="text" name="company" class="form-input" 
                                   value="<?php echo escape($contact['company']); ?>"
                                   placeholder="Company name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Title / Role</label>
                            <input type="text" name="title" class="form-input" 
                                   value="<?php echo escape($contact['title']); ?>"
                                   placeholder="CEO, Founder, etc.">
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Pipeline Stage</label>
                            <select name="stage" class="form-select">
                                <?php foreach ($stages as $stageKey => $stageData): ?>
                                    <option value="<?php echo escape($stageKey); ?>" 
                                            <?php echo $contact['stage'] === $stageKey ? 'selected' : ''; ?>>
                                        <?php echo escape($stageData['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>

                <!-- Interactions Timeline -->
                <div class="card">
                    <h2 class="card-title">Interactions</h2>
                    
                    <!-- Add Note Form -->
                    <form method="POST" class="add-note-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="add_interaction">
                        <input type="text" name="note_title" class="form-input note-title-input" 
                               placeholder="Note title (optional)" maxlength="255">
                        <textarea name="note" class="form-textarea" 
                                  placeholder="Add a note about your interaction..." required></textarea>
                        <button type="submit" class="btn btn-primary btn-sm">+ Add Note</button>
                    </form>
                    
                    <!-- Interactions List -->
                    <div class="interactions-list">
                        <?php if (empty($interactions)): ?>
                            <div class="empty-interactions">
                                No interactions yet. Add your first note above.
                            </div>
                        <?php else: ?>
                            <?php foreach ($interactions as $interaction): ?>
                                <?php 
                                // Use title if available, otherwise format the timestamp nicely
                                $displayTitle = !empty($interaction['title']) 
                                    ? $interaction['title'] 
                                    : date('l, F j, Y \a\t g:i A', strtotime($interaction['created_at']));
                                ?>
                                <div class="interaction-item" data-id="<?php echo $interaction['id']; ?>" onclick="toggleNote(this)">
                                    <div class="interaction-header">
                                        <div class="interaction-title">
                                            <span class="interaction-toggle">▶</span>
                                            <?php echo escape($displayTitle); ?>
                                        </div>
                                        <span class="interaction-time"><?php echo timeAgo($interaction['created_at']); ?></span>
                                    </div>
                                    <div class="interaction-content" style="display: none;">
                                        <div class="interaction-note-display">
                                            <div class="interaction-note"><?php echo escape($interaction['note']); ?></div>
                                        </div>
                                        <div class="interaction-meta">
                                            <div class="interaction-actions" onclick="event.stopPropagation();">
                                                <button type="button" class="interaction-edit" 
                                                        onclick="showEditForm(<?php echo $interaction['id']; ?>)">
                                                    Edit
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="delete_interaction">
                                                    <input type="hidden" name="interaction_id" value="<?php echo $interaction['id']; ?>">
                                                    <button type="submit" class="interaction-delete" 
                                                            onclick="return confirm('Delete this note?');">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <!-- Edit Form (hidden by default) -->
                                        <form method="POST" class="interaction-edit-form" id="edit-form-<?php echo $interaction['id']; ?>" 
                                              style="display: none;" onclick="event.stopPropagation();">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="edit_interaction">
                                            <input type="hidden" name="interaction_id" value="<?php echo $interaction['id']; ?>">
                                            <input type="text" name="note_title" class="form-input edit-title-input" 
                                                   value="<?php echo escape($interaction['title'] ?? ''); ?>"
                                                   placeholder="Note title (optional)" maxlength="255">
                                            <textarea name="note" class="form-textarea edit-note-input" required><?php echo escape($interaction['note']); ?></textarea>
                                            <div class="edit-form-actions">
                                                <button type="button" class="btn btn-cancel btn-sm" 
                                                        onclick="hideEditForm(<?php echo $interaction['id']; ?>)">Cancel</button>
                                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Social Media -->
            <div class="right-column">
                <!-- Social Media (handles + stats combined) -->
                <form method="POST" class="card">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_social">
                    
                    <h2 class="card-title">
                        Social Media
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </h2>
                    
                    <div class="social-grid">
                        <!-- YouTube -->
                        <div class="social-row">
                            <div class="social-platform">
                                <span class="stat-icon youtube">YT</span>
                                <span class="platform-name">YouTube</span>
                            </div>
                            <div class="social-inputs">
                                <input type="url" name="youtube_channel" class="form-input social-handle-input" 
                                       value="<?php echo escape($contact['youtube_channel']); ?>"
                                       placeholder="Channel URL">
                                <input type="number" name="stats_youtube" class="stat-input"
                                       value="<?php echo isset($socialStatsMap['youtube']) ? $socialStatsMap['youtube'] : ''; ?>"
                                       placeholder="Subs" min="0">
                            </div>
                        </div>
                        
                        <!-- Instagram -->
                        <div class="social-row">
                            <div class="social-platform">
                                <span class="stat-icon instagram">IG</span>
                                <span class="platform-name">Instagram</span>
                            </div>
                            <div class="social-inputs">
                                <input type="text" name="instagram_handle" class="form-input social-handle-input" 
                                       value="<?php echo escape($contact['instagram_handle']); ?>"
                                       placeholder="@username">
                                <input type="number" name="stats_instagram" class="stat-input"
                                       value="<?php echo isset($socialStatsMap['instagram']) ? $socialStatsMap['instagram'] : ''; ?>"
                                       placeholder="Followers" min="0">
                            </div>
                        </div>
                        
                        <!-- Twitter / X -->
                        <div class="social-row">
                            <div class="social-platform">
                                <span class="stat-icon twitter">X</span>
                                <span class="platform-name">Twitter / X</span>
                            </div>
                            <div class="social-inputs">
                                <input type="text" name="twitter_handle" class="form-input social-handle-input" 
                                       value="<?php echo escape($contact['twitter_handle']); ?>"
                                       placeholder="@username">
                                <input type="number" name="stats_twitter" class="stat-input"
                                       value="<?php echo isset($socialStatsMap['twitter']) ? $socialStatsMap['twitter'] : ''; ?>"
                                       placeholder="Followers" min="0">
                            </div>
                        </div>
                        
                        <!-- LinkedIn -->
                        <div class="social-row">
                            <div class="social-platform">
                                <span class="stat-icon linkedin">Li</span>
                                <span class="platform-name">LinkedIn</span>
                            </div>
                            <div class="social-inputs">
                                <input type="url" name="linkedin_url" class="form-input social-handle-input" 
                                       value="<?php echo escape($contact['linkedin_url']); ?>"
                                       placeholder="Profile URL">
                                <input type="number" name="stats_linkedin" class="stat-input"
                                       value="<?php echo isset($socialStatsMap['linkedin']) ? $socialStatsMap['linkedin'] : ''; ?>"
                                       placeholder="Followers" min="0">
                            </div>
                        </div>
                        
                        <!-- TikTok -->
                        <div class="social-row">
                            <div class="social-platform">
                                <span class="stat-icon tiktok">TT</span>
                                <span class="platform-name">TikTok</span>
                            </div>
                            <div class="social-inputs">
                                <input type="text" name="tiktok_handle" class="form-input social-handle-input" 
                                       value="<?php echo escape($contact['tiktok_handle']); ?>"
                                       placeholder="@username">
                                <input type="number" name="stats_tiktok" class="stat-input"
                                       value="<?php echo isset($socialStatsMap['tiktok']) ? $socialStatsMap['tiktok'] : ''; ?>"
                                       placeholder="Followers" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    $totalFollowers = array_sum($socialStatsMap);
                    if ($totalFollowers > 0):
                    ?>
                    <div class="total-reach">
                        <span class="reach-label">Total Reach</span>
                        <span class="reach-value"><?php echo SocialStats::formatFollowers($totalFollowers); ?></span>
                    </div>
                    <?php endif; ?>
                </form>
                
                <!-- Quick Info -->
                <div class="card">
                    <h2 class="card-title">Quick Info</h2>
                    <div style="font-size: 0.875rem; color: #6b7280;">
                        <p style="margin-bottom: 0.5rem;">
                            <strong>Created:</strong> <?php echo formatDate($contact['created_at']); ?>
                        </p>
                        <p>
                            <strong>Last Updated:</strong> <?php echo timeAgo($contact['updated_at']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleNote(element) {
            const content = element.querySelector('.interaction-content');
            const isExpanded = element.classList.contains('expanded');
            
            if (isExpanded) {
                content.style.display = 'none';
                element.classList.remove('expanded');
            } else {
                content.style.display = 'block';
                element.classList.add('expanded');
            }
        }

        function showEditForm(interactionId) {
            const item = document.querySelector(`.interaction-item[data-id="${interactionId}"]`);
            const noteDisplay = item.querySelector('.interaction-note-display');
            const meta = item.querySelector('.interaction-meta');
            const editForm = document.getElementById(`edit-form-${interactionId}`);
            
            noteDisplay.style.display = 'none';
            meta.style.display = 'none';
            editForm.style.display = 'block';
        }

        function hideEditForm(interactionId) {
            const item = document.querySelector(`.interaction-item[data-id="${interactionId}"]`);
            const noteDisplay = item.querySelector('.interaction-note-display');
            const meta = item.querySelector('.interaction-meta');
            const editForm = document.getElementById(`edit-form-${interactionId}`);
            
            noteDisplay.style.display = 'block';
            meta.style.display = 'flex';
            editForm.style.display = 'none';
        }
    </script>
</body>
</html>

