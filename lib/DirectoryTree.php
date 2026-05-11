<?php
class DirectoryTree {
    private $cacheDir;
    private $cacheFile;
    private $manifestFile;
    private $cacheTTL;
    private $tree;
    
    public function __construct($cacheTTL = 3600) {
        $this->cacheTTL = $cacheTTL;
        $this->cacheDir = dirname(__FILE__) . '/../data/';
        $this->cacheFile = $this->cacheDir . 'tree.json';
        $this->manifestFile = $this->cacheDir . 'cache_manifest.json';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function build($basePath) {
        $docsPath = dirname(__FILE__) . '/../' . $basePath;
        
        if (!is_dir($docsPath)) {
            return array();
        }
        
        $this->tree = $this->scanDirectory($docsPath, '');
        $this->saveCache();
        
        return $this->tree;
    }
    
    public function getTree() {
        if ($this->shouldRefreshCache()) {
            return $this->build('docs/');
        }
        
        if (file_exists($this->cacheFile)) {
            $content = file_get_contents($this->cacheFile);
            $this->tree = json_decode($content, true);
            return $this->tree;
        }
        
        return $this->build('docs/');
    }
    
    private function scanDirectory($path, $relativePath) {
        $items = array();
        
        $files = scandir($path);
        $folders = array();
        $mdFiles = array();
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $fullPath = $path . '/' . $file;
            
            if (is_dir($fullPath)) {
                $folders[] = $file;
            } elseif (preg_match('/\.md$/i', $file)) {
                $mdFiles[] = $file;
            }
        }
        
        sort($folders);
        sort($mdFiles);
        
        foreach ($folders as $folder) {
            $folderPath = $path . '/' . $folder;
            $folderRelative = $relativePath . ($relativePath ? '/' : '') . $folder;
            
            $children = $this->scanDirectory($folderPath, $folderRelative);
            
            $items[] = array(
                'type' => 'folder',
                'name' => $this->formatName($folder),
                'path' => $folderRelative,
                'children' => $children
            );
        }
        
        foreach ($mdFiles as $file) {
            $filePath = $path . '/' . $file;
            $fileRelative = $relativePath . ($relativePath ? '/' : '') . $file;
            $urlPath = preg_replace('/\.md$/i', '', $fileRelative);
            
            $items[] = array(
                'type' => 'file',
                'name' => $this->extractTitle($filePath, $file),
                'path' => $urlPath,
                'file' => $fileRelative
            );
        }
        
        return $items;
    }
    
    private function formatName($name) {
        $name = preg_replace('/[-_]+/', ' ', $name);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        return ucfirst(trim($name));
    }
    
    private function extractTitle($filePath, $fallbackName) {
        $content = file_get_contents($filePath);
        
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return $this->formatName(preg_replace('/\.md$/i', '', $fallbackName));
    }
    
    private function shouldRefreshCache() {
        if (!file_exists($this->cacheFile) || !file_exists($this->manifestFile)) {
            return true;
        }
        
        $manifest = $this->readManifest();
        
        if (isset($manifest['expires_at'])) {
            $expiresAt = strtotime($manifest['expires_at']);
            if (time() > $expiresAt) {
                return true;
            }
        }
        
        $docsPath = dirname(__FILE__) . '/../docs/';
        if (is_dir($docsPath)) {
            $docsMtime = $this->getDirectoryMtime($docsPath);
            
            if (isset($manifest['generated_at'])) {
                $cacheGenerated = strtotime($manifest['generated_at']);
                if ($docsMtime > $cacheGenerated) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function getDirectoryMtime($path) {
        $mtime = filemtime($path);
        
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $fullPath = $path . '/' . $file;
            
            if (is_dir($fullPath)) {
                $dirMtime = $this->getDirectoryMtime($fullPath);
                $mtime = max($mtime, $dirMtime);
            } else {
                $mtime = max($mtime, filemtime($fullPath));
            }
        }
        
        return $mtime;
    }
    
    private function saveCache() {
        $now = new DateTime();
        $expiresAt = clone $now;
        $expiresAt->modify('+' . $this->cacheTTL . ' seconds');
        
        $manifest = array(
            'version' => '1.0',
            'generated_at' => $now->format('c'),
            'expires_at' => $expiresAt->format('c'),
            'ttl' => $this->cacheTTL
        );
        
        file_put_contents($this->cacheFile, json_encode($this->tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($this->manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
    }
    
    private function readManifest() {
        if (!file_exists($this->manifestFile)) {
            return array();
        }
        
        $content = file_get_contents($this->manifestFile);
        return json_decode($content, true) ?: array();
    }
    
    public function refreshCache() {
        return $this->build('docs/');
    }
    
    public function getCacheInfo() {
        return $this->readManifest();
    }
    
    public function toJson() {
        $tree = $this->getTree();
        return json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    public function toArray() {
        return $this->getTree();
    }
}
