<?php
/**
 * Dashboard - Contact Actions
 * Handles contact CRUD actions (delete, duplicate, etc.)
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

// Get action and contact ID
$action = get('action');
$contactId = get('id');

if (!$action || !$contactId) {
    flashMessage('error', 'Invalid request.');
    redirect('contacts.php');
}

// Initialize Contacts class
$contactsManager = new Contacts($user['id']);

// Verify contact exists and belongs to user
$contact = $contactsManager->getContact($contactId, $user['id']);
if (!$contact) {
    flashMessage('error', 'Contact not found.');
    redirect('contacts.php');
}

switch ($action) {
    case 'delete':
        if ($contactsManager->deleteContact($contactId, $user['id'])) {
            flashMessage('success', 'Contact deleted successfully.');
        } else {
            flashMessage('error', 'Failed to delete contact.');
        }
        redirect('contacts.php');
        break;
        
    case 'duplicate':
        // Check if user can create more contacts
        $usage = $contactsManager->getUserUsage($user['id']);
        if (!$usage['can_create']) {
            flashMessage('error', 'You have reached your contact limit. Please upgrade to duplicate contacts.');
            redirect('contacts.php');
        }
        
        // Create a copy of the contact
        $duplicateData = [
            'name' => 'Copy of ' . $contact['name'],
            'email' => $contact['email'],
            'phone' => $contact['phone'],
            'company' => $contact['company'],
            'title' => $contact['title'],
            'stage' => 'identified', // Reset to initial stage
            'linkedin_url' => $contact['linkedin_url'],
            'twitter_handle' => $contact['twitter_handle'],
            'youtube_channel' => $contact['youtube_channel'],
            'instagram_handle' => $contact['instagram_handle'],
            'tiktok_handle' => $contact['tiktok_handle']
        ];
        
        $newContactId = $contactsManager->createContact($user['id'], $duplicateData);
        
        if ($newContactId) {
            flashMessage('success', 'Contact duplicated successfully.');
            redirect('contact-view.php?id=' . $newContactId);
        } else {
            flashMessage('error', 'Failed to duplicate contact.');
            redirect('contacts.php');
        }
        break;
        
    default:
        flashMessage('error', 'Invalid action.');
        redirect('contacts.php');
        break;
}

