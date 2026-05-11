                <article class="markdown-body" id="markdown-content">
                    <h1><?php echo htmlspecialchars($title); ?></h1>
                    <div class="content">
                        <?php echo $html; ?>
                    </div>
                </article>
                
                <?php if (!empty($headings)): ?>
                <aside class="toc" id="toc">
                    <h3>目录</h3>
                    <ul>
                        <?php foreach ($headings as $heading): ?>
                            <li class="toc-level-<?php echo $heading['level']; ?>">
                                <a href="#<?php echo $heading['id']; ?>"><?php echo htmlspecialchars($heading['text']); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        window.DOCS_CONFIG = {
            tree: <?php echo $treeJson; ?>,
            currentPath: '<?php echo htmlspecialchars($currentPath); ?>',
            baseUrl: '/'
        };
    </script>
