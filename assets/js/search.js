(function() {
    'use strict';
    
    const CONFIG = window.DOCS_CONFIG || {
        tree: [],
        currentPath: '/',
        baseUrl: '/'
    };
    
    class SearchEngine {
        constructor() {
            this.index = null;
            this.documents = [];
            this.indexed = false;
            this.init();
        }
        
        async init() {
            this.index = new FlexSearch.Document({
                document: {
                    id: 'path',
                    index: ['title', 'content'],
                    store: ['title', 'path']
                },
                tokenize: 'forward',
                resolution: 9,
                cache: true
            });
            
            await this.buildIndex();
            this.bindEvents();
        }
        
        async buildIndex() {
            if (CONFIG.tree.length === 0) {
                console.log('No documents to index');
                return;
            }
            
            const files = this.flattenTree(CONFIG.tree);
            
            for (const file of files) {
                try {
                    const content = await this.fetchDocument(file.path);
                    if (content) {
                        this.index.add({
                            path: file.path,
                            title: file.name,
                            content: this.stripMarkdown(content)
                        });
                        this.documents.push({
                            path: file.path,
                            name: file.name
                        });
                    }
                } catch (error) {
                    console.error('Error indexing document:', file.path, error);
                }
            }
            
            this.indexed = true;
            console.log('Search index built:', this.documents.length, 'documents');
        }
        
        flattenTree(nodes, results = []) {
            if (!nodes) return results;
            
            nodes.forEach(node => {
                if (node.type === 'file') {
                    results.push(node);
                } else if (node.type === 'folder' && node.children) {
                    this.flattenTree(node.children, results);
                }
            });
            
            return results;
        }
        
        async fetchDocument(path) {
            try {
                const response = await fetch(`${CONFIG.baseUrl}${path}?raw=1`);
                if (response.ok) {
                    return await response.text();
                }
            } catch (error) {
                console.error('Error fetching document:', path, error);
            }
            return null;
        }
        
        stripMarkdown(text) {
            if (!text) return '';
            
            return text
                .replace(/^#.*$/gm, '')
                .replace(/\*\*(.+?)\*\*/g, '$1')
                .replace(/\*(.+?)\*/g, '$1')
                .replace(/`(.+?)`/g, '$1')
                .replace(/```[\s\S]*?```/g, '')
                .replace(/\[(.+?)\]\(.+?\)/g, '$1')
                .replace(/!\[.*?\]\(.+?\)/g, '')
                .replace(/^>\s*/gm, '')
                .replace(/^[-*]\s+/gm, '')
                .replace(/^\d+\.\s+/gm, '')
                .replace(/\|.*?\|/g, '')
                .replace(/[-=]{3,}/g, '')
                .replace(/\$([^$]+)\$/g, '$1')
                .replace(/\$\$[\s\S]*?\$\$/g, '')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
        }
        
        search(query) {
            if (!this.indexed || !query.trim()) {
                return [];
            }
            
            const results = this.index.search(query, {
                limit: 10,
                enrich: true
            });
            
            const uniqueResults = new Map();
            
            results.forEach(fieldResult => {
                if (fieldResult.result) {
                    fieldResult.result.forEach(item => {
                        if (!uniqueResults.has(item.id)) {
                            const doc = this.documents.find(d => d.path === item.id);
                            if (doc) {
                                uniqueResults.set(item.id, {
                                    path: doc.path,
                                    name: doc.name,
                                    score: item.score || 1
                                });
                            }
                        }
                    });
                }
            });
            
            return Array.from(uniqueResults.values())
                .sort((a, b) => b.score - a.score);
        }
        
        bindEvents() {
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            
            if (!searchInput || !searchResults) return;
            
            let debounceTimer;
            
            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const query = e.target.value.trim();
                    
                    if (query.length < 2) {
                        searchResults.classList.remove('active');
                        return;
                    }
                    
                    const results = this.search(query);
                    this.displayResults(results, searchResults);
                }, 300);
            });
            
            searchInput.addEventListener('focus', () => {
                if (searchInput.value.trim().length >= 2) {
                    const query = searchInput.value.trim();
                    const results = this.search(query);
                    this.displayResults(results, searchResults);
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.remove('active');
                }
            });
            
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    searchResults.classList.remove('active');
                    searchInput.blur();
                }
                
                if (e.key === 'Enter') {
                    const firstResult = searchResults.querySelector('.search-result-item');
                    if (firstResult) {
                        firstResult.click();
                    }
                }
                
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const items = searchResults.querySelectorAll('.search-result-item');
                    const activeItem = searchResults.querySelector('.search-result-item:focus');
                    let currentIndex = Array.from(items).indexOf(activeItem);
                    
                    if (e.key === 'ArrowDown') {
                        currentIndex = (currentIndex + 1) % items.length;
                    } else {
                        currentIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                    }
                    
                    if (items[currentIndex]) {
                        items[currentIndex].focus();
                    }
                }
            });
        }
        
        displayResults(results, container) {
            if (results.length === 0) {
                container.innerHTML = '<div class="search-no-results">未找到相关文档</div>';
                container.classList.add('active');
                return;
            }
            
            let html = '';
            
            results.forEach(result => {
                const pathParts = result.path.split('/');
                const folder = pathParts.length > 1 ? pathParts.slice(0, -1).join('/') : '';
                
                html += `
                    <div class="search-result-item" data-path="${result.path}" tabindex="0">
                        <div class="title">${this.escapeHtml(result.name)}</div>
                        <div class="path">${this.escapeHtml(folder)}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            container.classList.add('active');
            
            container.querySelectorAll('.search-result-item').forEach(item => {
                item.addEventListener('click', () => {
                    const path = item.getAttribute('data-path');
                    window.location.href = CONFIG.baseUrl + path;
                });
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
            new SearchEngine();
        });
    } else {
        new SearchEngine();
    }
})();
