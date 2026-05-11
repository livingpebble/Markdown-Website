<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($title); ?>">
    <title><?php echo htmlspecialchars($title); ?> - 文档中心</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flexsearch@0.7.43/bundle/flexsearch.bundle.min.js">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/markdown.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h1>📚 文档中心</h1>
                </div>
                <button class="mobile-toggle" id="mobile-toggle" aria-label="切换菜单">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            
            <div class="search-container">
                <input type="text" id="search-input" placeholder="搜索文档... (Ctrl+K)" autocomplete="off">
                <div class="search-results" id="search-results"></div>
            </div>
            
            <nav class="directory-tree" id="directory-tree">
                <div class="loading">加载目录树...</div>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="content-wrapper">
