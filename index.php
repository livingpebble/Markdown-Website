<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600);

require_once __DIR__ . '/lib/DirectoryTree.php';
require_once __DIR__ . '/lib/DocumentParser.php';

header('Content-Type: text/html; charset=utf-8');

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

if (strpos($requestUri, $scriptName) === 0) {
    $pathInfo = substr($requestUri, strlen($scriptName));
} else {
    $pathInfo = $requestUri;
}

$pathInfo = parse_url($pathInfo, PHP_URL_PATH);
$pathInfo = trim($pathInfo, '/');

if (empty($pathInfo)) {
    $pathInfo = 'index';
}

if (isset($_GET['refresh'])) {
    $tree = new DirectoryTree(CACHE_TTL);
    $tree->refreshCache();
}

if (isset($_GET['tree']) || isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $tree = new DirectoryTree(CACHE_TTL);
    echo $tree->toJson();
    exit;
}

$mdPath = $pathInfo . '.md';
if (strpos($mdPath, '/') === 0) {
    $mdPath = substr($mdPath, 1);
}

$parser = new DocumentParser();

if (!$parser->fileExists($mdPath)) {
    $indexPath = $pathInfo . '/index.md';
    if ($parser->fileExists($indexPath)) {
        $mdPath = $indexPath;
    } else {
        http_response_code(404);
        $mdPath = '404.md';
        if (!$parser->fileExists($mdPath)) {
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>404 - Not Found</title></head><body><h1>404 - Page Not Found</h1><p>The requested page was not found.</p></body></html>';
            exit;
        }
    }
}

$html = $parser->parse($mdPath);
$rawContent = $parser->getRawContent($mdPath);
$title = $parser->getTitle($rawContent, basename($mdPath, '.md'));
$headings = $parser->extractHeadings($html);

$tree = new DirectoryTree(CACHE_TTL);
$treeJson = $tree->toJson();
$currentPath = '/' . $pathInfo;

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
require_once __DIR__ . '/templates/content.php';
require_once __DIR__ . '/templates/footer.php';
