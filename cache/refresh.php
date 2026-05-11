<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../lib/DirectoryTree.php';

echo "========================================\n";
echo "Markdown Website Cache Refresh Tool\n";
echo "========================================\n\n";

$tree = new DirectoryTree(3600);

echo "缓存信息:\n";
echo "----------------------------------------\n";

$cacheInfo = $tree->getCacheInfo();
if (!empty($cacheInfo)) {
    echo "生成时间: " . $cacheInfo['generated_at'] . "\n";
    echo "过期时间: " . $cacheInfo['expires_at'] . "\n";
    echo "TTL: " . $cacheInfo['ttl'] . " 秒\n";
    echo "\n";
    
    $expiresAt = strtotime($cacheInfo['expires_at']);
    $now = time();
    $remaining = $expiresAt - $now;
    
    if ($remaining > 0) {
        echo "缓存将在 " . floor($remaining / 60) . " 分钟后过期\n";
    } else {
        echo "⚠️  缓存已过期\n";
    }
} else {
    echo "没有找到缓存信息\n";
}

echo "\n";
echo "正在刷新缓存...\n";

$result = $tree->refreshCache();

if ($result !== false) {
    echo "✅ 缓存刷新成功!\n";
    echo "\n";
    
    $newInfo = $tree->getCacheInfo();
    echo "新的缓存信息:\n";
    echo "----------------------------------------\n";
    echo "生成时间: " . $newInfo['generated_at'] . "\n";
    echo "过期时间: " . $newInfo['expires_at'] . "\n";
    echo "\n";
    
    echo "统计信息:\n";
    echo "----------------------------------------\n";
    $fileCount = count($result);
    echo "文档数量: " . $fileCount . "\n";
} else {
    echo "❌ 缓存刷新失败!\n";
    exit(1);
}

echo "\n========================================\n";
echo "Done!\n";
echo "========================================\n";
