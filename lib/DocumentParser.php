<?php
require_once dirname(__FILE__) . '/Parsedown.php';

class DocumentParser {
    private $parsedown;
    private $basePath;
    
    public function __construct() {
        $this->parsedown = new Parsedown();
        $this->parsedown->setBreaksEnabled(true);
        $this->parsedown->setMarkupEscaped(true);
        $this->basePath = dirname(__FILE__) . '/../docs/';
    }
    
    public function parse($filePath) {
        $fullPath = $this->basePath . $filePath;
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        $content = file_get_contents($fullPath);
        
        $content = $this->processRelativePaths($content, $filePath);
        
        $html = $this->parsedown->text($content);
        
        return $html;
    }
    
    public function getTitle($content, $fallbackName = '') {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        
        if ($fallbackName) {
            $fallbackName = preg_replace('/[-_]+/', ' ', $fallbackName);
            $fallbackName = preg_replace('/([a-z])([A-Z])/', '$1 $2', $fallbackName);
            return ucfirst(preg_replace('/\.md$/i', '', $fallbackName));
        }
        
        return 'Untitled';
    }
    
    public function extractHeadings($html) {
        $headings = array();
        
        if (preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $level = intval($match[1]);
                $text = strip_tags($match[2]);
                $id = $this->generateHeadingId($text);
                
                $headings[] = array(
                    'level' => $level,
                    'text' => $text,
                    'id' => $id
                );
            }
        }
        
        return $headings;
    }
    
    private function generateHeadingId($text) {
        $id = preg_replace('/[^\w\s-]/', '', $text);
        $id = preg_replace('/\s+/', '-', $id);
        $id = strtolower($id);
        $id = trim($id, '-');
        
        return $id ?: 'heading';
    }
    
    private function processRelativePaths($content, $currentFile) {
        $currentDir = dirname($currentFile);
        if ($currentDir === '.') {
            $currentDir = '';
        }
        
        $content = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^\)]+)\)/',
            function($matches) use ($currentDir) {
                $alt = $matches[1];
                $path = $matches[2];
                
                if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
                    return $matches[0];
                }
                
                $fullPath = $this->resolvePath($path, $currentDir);
                
                return '![' . $alt . '](' . $fullPath . ')';
            },
            $content
        );
        
        $content = preg_replace_callback(
            '/\[([^\]]+)\]\(([^\)]+)\)/',
            function($matches) use ($currentDir) {
                $text = $matches[1];
                $path = $matches[2];
                
                if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
                    return $matches[0];
                }
                
                if (strpos($path, '#') === 0) {
                    return $matches[0];
                }
                
                $fullPath = $this->resolvePath($path, $currentDir);
                
                return '[' . $text . '](' . $fullPath . ')';
            },
            $content
        );
        
        return $content;
    }
    
    private function resolvePath($path, $currentDir) {
        if ($currentDir && strpos($path, '/') !== 0) {
            $path = $currentDir . '/' . $path;
        }
        
        $parts = explode('/', $path);
        $resolved = array();
        
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($resolved);
            } elseif ($part !== '.' && $part !== '') {
                $resolved[] = $part;
            }
        }
        
        return implode('/', $resolved);
    }
    
    public function fileExists($filePath) {
        $fullPath = $this->basePath . $filePath;
        return file_exists($fullPath);
    }
    
    public function getRawContent($filePath) {
        $fullPath = $this->basePath . $filePath;
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        return file_get_contents($fullPath);
    }
}
