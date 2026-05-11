(function() {
    'use strict';
    
    const CONFIG = window.DOCS_CONFIG || {
        tree: [],
        currentPath: '/',
        baseUrl: '/'
    };
    
    class DocsApp {
        constructor() {
            this.tree = CONFIG.tree;
            this.currentPath = CONFIG.currentPath;
            this.baseUrl = CONFIG.baseUrl;
            this.init();
        }
        
        init() {
            this.renderTree();
            this.setupMobileToggle();
            this.highlightCurrentPage();
            this.setupSmoothScroll();
            this.setupKeyboardShortcuts();
        }
        
        renderTree() {
            const container = document.getElementById('directory-tree');
            if (!container) return;
            
            if (!this.tree || this.tree.length === 0) {
                container.innerHTML = '<div class="empty-state">暂无文档</div>';
                return;
            }
            
            container.innerHTML = this.renderTreeNodes(this.tree);
            this.bindTreeEvents();
        }
        
        renderTreeNodes(nodes, level = 0) {
            if (!nodes || nodes.length === 0) return '';
            
            let html = '';
            
            nodes.forEach(node => {
                if (node.type === 'folder') {
                    html += this.renderFolder(node, level);
                } else if (node.type === 'file') {
                    html += this.renderFile(node, level);
                }
            });
            
            return html;
        }
        
        renderFolder(folder, level) {
            const hasChildren = folder.children && folder.children.length > 0;
            const path = '/' + folder.path;
            const isActive = this.isPathActive(path);
            const hasActiveChild = this.hasActiveChild(folder);
            
            let html = `<div class="tree-item tree-folder" data-path="${folder.path}">`;
            html += `<div class="tree-item-content ${(isActive || hasActiveChild) ? 'active' : ''}" data-type="folder" data-path="${folder.path}">`;
            html += `<span class="tree-icon ${hasChildren ? '' : 'no-children'}">${hasChildren ? '▶' : ''}</span>`;
            html += `<span class="tree-name">${this.escapeHtml(folder.name)}</span>`;
            html += '</div>';
            
            if (hasChildren) {
                html += `<div class="tree-children">`;
                html += this.renderTreeNodes(folder.children, level + 1);
                html += '</div>';
            }
            
            html += '</div>';
            
            return html;
        }
        
        renderFile(file, level) {
            const path = '/' + file.path;
            const isActive = this.currentPath === path;
            
            let html = `<div class="tree-item tree-file" data-path="${file.path}">`;
            html += `<a href="${this.baseUrl}${file.path}" class="tree-item-content ${isActive ? 'active' : ''}" data-type="file" data-path="${file.path}">`;
            html += `<span class="tree-icon"></span>`;
            html += `<span class="tree-name">${this.escapeHtml(file.name)}</span>`;
            html += '</a>';
            html += '</div>';
            
            return html;
        }
        
        bindTreeEvents() {
            const items = document.querySelectorAll('.tree-folder > .tree-item-content');
            
            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const children = item.parentElement.querySelector('.tree-children');
                    
                    if (children) {
                        const isExpanded = children.classList.contains('expanded');
                        const icon = item.querySelector('.tree-icon');
                        
                        if (isExpanded) {
                            children.classList.remove('expanded');
                            icon.classList.remove('expanded');
                        } else {
                            children.classList.add('expanded');
                            icon.classList.add('expanded');
                        }
                    }
                });
            });
            
            this.autoExpandCurrentPath();
        }
        
        autoExpandCurrentPath() {
            const currentPath = this.currentPath.replace(/^\//, '');
            const parts = currentPath.split('/');
            
            let path = '';
            for (let i = 0; i < parts.length - 1; i++) {
                path += (i > 0 ? '/' : '') + parts[i];
                const folder = document.querySelector(`.tree-folder[data-path="${path}"] > .tree-children`);
                if (folder) {
                    folder.classList.add('expanded');
                    const icon = document.querySelector(`.tree-folder[data-path="${path}"] > .tree-item-content .tree-icon`);
                    if (icon) {
                        icon.classList.add('expanded');
                    }
                }
            }
        }
        
        isPathActive(path) {
            return this.currentPath === path;
        }
        
        hasActiveChild(folder) {
            if (!folder.children) return false;
            
            const currentPath = this.currentPath.replace(/^\//, '');
            
            return folder.children.some(child => {
                if (child.type === 'file') {
                    return '/' + child.path === this.currentPath;
                } else if (child.type === 'folder') {
                    return this.hasActiveChild(child);
                }
                return false;
            });
        }
        
        highlightCurrentPage() {
            const currentPath = this.currentPath.replace(/^\//, '');
            const activeItem = document.querySelector(`.tree-item[data-path="${currentPath}"]`);
            
            if (activeItem) {
                const content = activeItem.querySelector('.tree-item-content');
                if (content) {
                    content.classList.add('active');
                }
            }
        }
        
        setupMobileToggle() {
            const toggle = document.getElementById('mobile-toggle');
            const sidebar = document.getElementById('sidebar');
            
            if (!toggle || !sidebar) return;
            
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                
                if (!document.querySelector('.overlay')) {
                    const overlay = document.createElement('div');
                    overlay.className = 'overlay';
                    document.body.appendChild(overlay);
                    
                    overlay.addEventListener('click', () => {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('active');
                    });
                } else {
                    const overlay = document.querySelector('.overlay');
                    if (overlay) {
                        overlay.classList.toggle('active');
                    }
                }
            });
        }
        
        setupSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = anchor.getAttribute('href');
                    const target = document.querySelector(targetId);
                    
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
        
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    const searchInput = document.getElementById('search-input');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }
            });
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new DocsApp();
        });
    } else {
        new DocsApp();
    }
})();
