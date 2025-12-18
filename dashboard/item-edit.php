<?php
/**
 * Edit Item Page
 * Form to edit an existing item
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

$itemsManager = new Items();

// Get item ID from URL
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$itemId) {
    flashMessage('error', 'Invalid item ID.');
    redirect('/dashboard/');
}

// Get the item (ensures user owns it)
$item = $itemsManager->getItem($itemId, $user['id']);

if (!$item) {
    flashMessage('error', 'Item not found or you do not have permission to edit it.');
    redirect('/dashboard/');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        flashMessage('error', 'Title is required.');
    } else {
        $success = $itemsManager->updateItem($itemId, $user['id'], $title, $description);
        
        if ($success) {
            flashMessage('success', 'Item updated successfully!');
            redirect('/dashboard/');
        } else {
            flashMessage('error', 'Failed to update item. Please try again.');
        }
    }
}

$pageTitle = 'Edit Item';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #1f2937;
            min-height: 100vh;
        }

        .top-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
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
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .main-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
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
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        .flash-messages {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }

        .flash-message {
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: white;
            border-left: 4px solid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .flash-message.success {
            border-left-color: #10b981;
            color: #065f46;
        }

        .flash-message.error {
            border-left-color: #ef4444;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .form-card {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="header-content">
            <a href="<?php echo url('/dashboard/'); ?>" class="site-logo">
                <img src="<?php echo url('assets/images/logo-small.png'); ?>" alt="<?php echo SITE_NAME; ?>">
                <span><?php echo SITE_NAME; ?></span>
            </a>
            <a href="<?php echo url('/dashboard/'); ?>" class="back-link">
                ← Back to Items
            </a>
        </div>
    </header>

    <?php if (hasFlashMessages()): ?>
        <div class="flash-messages">
            <?php foreach (getFlashMessages() as $flash): ?>
                <div class="flash-message <?php echo escape($flash['type']); ?>">
                    <?php echo escape($flash['message']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main class="main-container">
        <div class="form-card">
            <h1 class="form-title">Edit Item</h1>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title" class="form-label">Title *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-input" 
                        placeholder="Enter item title..."
                        value="<?php echo escape($item['title']); ?>"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea" 
                        placeholder="Add a description (optional)..."
                    ><?php echo escape($item['description']); ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="<?php echo url('/dashboard/'); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>

