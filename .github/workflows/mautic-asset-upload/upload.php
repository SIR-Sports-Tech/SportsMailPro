<?php

declare(strict_types=1);

use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

require __DIR__.'/vendor/autoload.php';

if ('cli' !== php_sapi_name()) {
    exit('This script can only run on the command line');
}

$vars = [
    1 => 'instanceUrl',
    2 => 'username',
    3 => 'password',
    4 => 'mauticVersion',
    5 => 'assetCategoryId',
    6 => 'remoteAssetUrl',
];

foreach ($vars as $id => $var) {
    if (empty($_SERVER['argv'][$id])) {
        echo "Argument {$id} ({$var}) is missing. Run this script as \"php upload.php ".implode(' ', $vars)."\"\n";
        exit(1);
    }

    $$var = $_SERVER['argv'][$id];
}

// Validate the remote asset URL for security
if (!filter_var($remoteAssetUrl, FILTER_VALIDATE_URL)) {
    echo "Error: Invalid URL format for remote asset: {$remoteAssetUrl}\n";
    exit(1);
}

// Ensure the URL uses HTTPS for security
$parsedUrl = parse_url($remoteAssetUrl);
if (!isset($parsedUrl['scheme']) || $parsedUrl['scheme'] !== 'https') {
    echo "Error: Remote asset URL must use HTTPS protocol\n";
    exit(1);
}

// Verify the URL is from GitHub releases (required for Mautic release process)
if (!isset($parsedUrl['host']) || !preg_match('/^github\.com$/i', $parsedUrl['host'])) {
    echo "Error: Remote asset URL must be from github.com for security\n";
    exit(1);
}

// Set up the authentication
$settings = [
    'userName'   => $username,
    'password'   => $password,
];

// Initiate the auth object specifying to use BasicAuth
$initAuth = new ApiAuth();
$auth     = $initAuth->newAuth($settings, 'BasicAuth');
$api      = new MauticApi();

/** @var Mautic\Api\Assets */
$assetApi = $api->newApi('assets', $auth, $instanceUrl);

/**
 * Create the asset with remote storage location.
 * This references the GitHub release asset directly without uploading to local storage.
 */
$data = [
    'title'           => "Mautic {$mauticVersion}",
    'storageLocation' => 'remote',
    'remotePath'      => $remoteAssetUrl,
    'category'        => $assetCategoryId,
    'isPublished'     => true,
];

$response = $assetApi->create($data);

if (isset($response['error'])) {
    echo $response['error']['code'].': '.$response['error']['message']."\n";
    exit(1);
}

if (!isset($response['asset']['id']) || !isset($response['asset']['downloadUrl'])) {
    echo "Unknown error occurred while creating asset. Please debug locally.\n";
    exit(1);
}

echo 'Successfully created asset with ID '.$response['asset']['id']." 🚀\n";
