<?php
/**
 * Dashboard - Export Contacts to CSV
 * Exports all user contacts to a CSV file
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Contacts.php';
require_once __DIR__ . '/../includes/SocialStats.php';
require_once __DIR__ . '/../includes/helpers.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
if (!$user) {
    flashMessage('error', 'Unable to load user data.');
    redirect('../auth/logout.php');
}

// Initialize classes
$contactsManager = new Contacts($user['id']);
$socialStatsManager = new SocialStats();

// Get all contacts
$contacts = $contactsManager->getUserContacts($user['id']);

if (empty($contacts)) {
    flashMessage('info', 'No contacts to export.');
    redirect('contacts.php');
}

// Get stages for readable labels
$stages = Contacts::getStages();

// Get social stats for all contacts
$allSocialStats = [];
foreach ($contacts as $contact) {
    $stats = $socialStatsManager->getContactStats($contact['id'], $user['id']);
    $statsMap = [];
    foreach ($stats as $stat) {
        $statsMap[$stat['platform']] = $stat['followers'];
    }
    $allSocialStats[$contact['id']] = $statsMap;
}

// Set headers for CSV download
$filename = 'contacts_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'Name',
    'Email',
    'Phone',
    'Company',
    'Title',
    'Stage',
    'LinkedIn URL',
    'Twitter Handle',
    'YouTube Channel',
    'Instagram Handle',
    'TikTok Handle',
    'YouTube Subscribers',
    'Instagram Followers',
    'Twitter Followers',
    'LinkedIn Connections',
    'TikTok Followers',
    'Created Date',
    'Last Updated'
];

fputcsv($output, $headers);

// Write contact rows
foreach ($contacts as $contact) {
    $socialStats = $allSocialStats[$contact['id']] ?? [];
    
    $row = [
        $contact['name'],
        $contact['email'] ?? '',
        $contact['phone'] ?? '',
        $contact['company'] ?? '',
        $contact['title'] ?? '',
        $stages[$contact['stage']]['name'] ?? $contact['stage'],
        $contact['linkedin_url'] ?? '',
        $contact['twitter_handle'] ?? '',
        $contact['youtube_channel'] ?? '',
        $contact['instagram_handle'] ?? '',
        $contact['tiktok_handle'] ?? '',
        $socialStats['youtube'] ?? '',
        $socialStats['instagram'] ?? '',
        $socialStats['twitter'] ?? '',
        $socialStats['linkedin'] ?? '',
        $socialStats['tiktok'] ?? '',
        formatDate($contact['created_at'], 'Y-m-d H:i:s'),
        formatDate($contact['updated_at'], 'Y-m-d H:i:s')
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit;

