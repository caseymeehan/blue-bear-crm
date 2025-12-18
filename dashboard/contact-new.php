<?php
/**
 * Dashboard - New Contact Form
 * Create a new partnership contact
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

// Check if user can create more contacts
$usage = $contactsManager->getUserUsage($user['id']);
if (!$usage['can_create']) {
    flashMessage('error', 'You have reached your contact limit. Please upgrade your plan to add more contacts.');
    redirect('contacts.php');
}

// Get all stages
$stages = Contacts::getStages();

// Handle form submission
$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'company' => '',
    'title' => '',
    'stage' => 'identified',
    'linkedin_url' => '',
    'twitter_handle' => '',
    'youtube_channel' => '',
    'instagram_handle' => '',
    'tiktok_handle' => ''
];

if (isPost()) {
    // Validate CSRF token
    if (!validateCSRFToken(post('csrf_token'))) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Collect form data
        $formData = [
            'name' => trim(post('name', '')),
            'email' => trim(post('email', '')),
            'phone' => trim(post('phone', '')),
            'company' => trim(post('company', '')),
            'title' => trim(post('title', '')),
            'stage' => post('stage', 'identified'),
            'linkedin_url' => trim(post('linkedin_url', '')),
            'twitter_handle' => trim(post('twitter_handle', '')),
            'youtube_channel' => trim(post('youtube_channel', '')),
            'instagram_handle' => trim(post('instagram_handle', '')),
            'tiktok_handle' => trim(post('tiktok_handle', ''))
        ];
        
        // Validate required fields
        if (empty($formData['name'])) {
            $errors[] = 'Name is required.';
        }
        
        // Validate email if provided
        if (!empty($formData['email']) && !isValidEmail($formData['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Validate stage
        if (!Contacts::isValidStage($formData['stage'])) {
            $errors[] = 'Invalid stage selected.';
        }
        
        // Create contact if no errors
        if (empty($errors)) {
            $contactId = $contactsManager->createContact($user['id'], $formData);
            
            if ($contactId) {
                flashMessage('success', 'Contact created successfully!');
                redirect('contact-view.php?id=' . $contactId);
            } else {
                $errors[] = 'Failed to create contact. Please try again.';
            }
        }
    }
}

// Page title
$pageTitle = 'New Contact';
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

        /* Main Container */
        .main-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

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

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-label .required {
            color: #ef4444;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .form-hint {
            font-size: 0.8125rem;
            color: #6b7280;
            margin-top: 0.375rem;
        }

        /* Stage Select */
        .stage-select-wrapper {
            position: relative;
        }

        .form-select {
            appearance: none;
            background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 1rem center;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        /* Errors */
        .error-list {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .error-list ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .error-list li {
            color: #991b1b;
            font-size: 0.9375rem;
            margin-bottom: 0.25rem;
        }

        .error-list li:last-child {
            margin-bottom: 0;
        }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
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

        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        /* Social Icons in Form */
        .social-input-wrapper {
            position: relative;
        }

        .social-input-wrapper .form-input {
            padding-left: 2.75rem;
        }

        .social-input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #6b7280;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .form-card {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
            
            <a href="<?php echo url('/dashboard/contacts.php'); ?>" class="back-link">
                ← Back to Contacts
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">New Contact</h1>
            <p class="page-subtitle">Add a new partnership contact to your rolodex</p>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="form-card">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Basic Info -->
            <div class="form-section">
                <h2 class="section-title">Basic Information</h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">
                            Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               class="form-input" 
                               placeholder="John Doe"
                               value="<?php echo escape($formData['name']); ?>"
                               required
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" 
                               name="email" 
                               class="form-input" 
                               placeholder="john@company.com"
                               value="<?php echo escape($formData['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" 
                               name="phone" 
                               class="form-input" 
                               placeholder="+1 (555) 000-0000"
                               value="<?php echo escape($formData['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Company</label>
                        <input type="text" 
                               name="company" 
                               class="form-input" 
                               placeholder="Acme Inc."
                               value="<?php echo escape($formData['company']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Title / Role</label>
                        <input type="text" 
                               name="title" 
                               class="form-input" 
                               placeholder="CEO, Founder, etc."
                               value="<?php echo escape($formData['title']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Pipeline Stage -->
            <div class="form-section">
                <h2 class="section-title">Pipeline Stage</h2>
                <div class="form-group">
                    <label class="form-label">Current Stage</label>
                    <div class="stage-select-wrapper">
                        <select name="stage" class="form-select">
                            <?php foreach ($stages as $stageKey => $stageData): ?>
                                <option value="<?php echo escape($stageKey); ?>" 
                                        <?php echo $formData['stage'] === $stageKey ? 'selected' : ''; ?>>
                                    <?php echo escape($stageData['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Social Media -->
            <div class="form-section">
                <h2 class="section-title">Social Media</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">LinkedIn URL</label>
                        <input type="url" 
                               name="linkedin_url" 
                               class="form-input" 
                               placeholder="https://linkedin.com/in/username"
                               value="<?php echo escape($formData['linkedin_url']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Twitter / X Handle</label>
                        <input type="text" 
                               name="twitter_handle" 
                               class="form-input" 
                               placeholder="@username"
                               value="<?php echo escape($formData['twitter_handle']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">YouTube Channel</label>
                        <input type="url" 
                               name="youtube_channel" 
                               class="form-input" 
                               placeholder="https://youtube.com/@channel"
                               value="<?php echo escape($formData['youtube_channel']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instagram Handle</label>
                        <input type="text" 
                               name="instagram_handle" 
                               class="form-input" 
                               placeholder="@username"
                               value="<?php echo escape($formData['instagram_handle']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">TikTok Handle</label>
                        <input type="text" 
                               name="tiktok_handle" 
                               class="form-input" 
                               placeholder="@username"
                               value="<?php echo escape($formData['tiktok_handle']); ?>">
                    </div>
                </div>
                <p class="form-hint">You can add social stats after creating the contact.</p>
            </div>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>+</span> Create Contact
                </button>
                <a href="<?php echo url('/dashboard/contacts.php'); ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </main>
</body>
</html>

