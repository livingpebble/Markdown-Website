# Markdown 文档网站项目计划（修订版）

## 项目概述
将文件夹中的 Markdown 文档按照现有组织结构转换为 PHP 网站，支持：
- 左侧文件夹目录导航
- 代码语法高亮
- 数学公式渲染
- 全文搜索功能
- 无需数据库，纯文档更新方式

## 核心设计决策

### 1. 依赖管理方案
**决定**: 不使用 Composer，直接引入第三方库
- **Parsedown**: 直接下载 `Parsedown.php` 到 `lib/` 目录
- **Prism.js**: 直接下载到 `assets/` 目录
- **KaTeX**: CDN 引入（数学公式）
- **FlexSearch**: 前端搜索库（轻量、快速）

**优势**: 
- 零依赖安装，适合各种服务器环境
- 迁移简单，只需复制文件
- 避免 Composer 版本冲突问题

### 2. URL 路由方案
**选择**: 单一入口 `index.php` + PATH_INFO + `.htaccess` 重写

示例路由：
```
/                           → docs/index.md
/docs/folder/page          → docs/folder/page.md
/docs/folder/               → docs/folder/index.md
```

## 项目目录结构

```
website/
├── index.php                 # 单一入口文件
├── .htaccess                 # Apache URL 重写规则
│
├── lib/                      # 核心库
│   ├── Parsedown.php         # Markdown 解析器（直接引入）
│   ├── DirectoryTree.php     # 目录树构建器
│   └── DocumentParser.php    # 文档解析封装
│
├── docs/                     # Markdown 文档目录（用户编辑区域）
│   ├── index.md              # 首页
│   ├── guide/
│   │   ├── index.md
│   │   └── getting-started.md
│   └── api/
│       ├── index.md
│       └── endpoints.md
│
├── templates/                # PHP 模板文件
│   ├── header.php            # HTML 头部
│   ├── sidebar.php           # 侧边栏
│   ├── content.php           # 内容区域
│   └── footer.php            # 页脚
│
├── assets/                   # 静态资源
│   ├── css/
│   │   ├── style.css         # 主样式
│   │   ├── markdown.css      # Markdown 内容样式
│   │   ├── sidebar.css       # 侧边栏样式
│   │   └── prism.css         # 代码高亮样式
│   │
│   ├── js/
│   │   ├── main.js           # 主脚本（目录、导航）
│   │   ├── search.js         # 搜索功能
│   │   └── prism.js          # 代码高亮（精简版）
│   │
│   └── fonts/                # 字体文件（如需）
│
├── data/                     # 运行时数据
│   ├── tree.json             # 缓存的目录树
│   └── cache_manifest.json   # 缓存元数据（时间戳、版本）
│
└── vendor/                   # 第三方库直接存放（不使用 Composer）
    └── parsedown/
        └── Parsedown.php
```

## 核心功能模块

### 1. DirectoryTree.php - 目录树生成器（带缓存）

**功能**:
- 递归扫描 `docs/` 目录
- 识别文件夹和 Markdown 文件
- 生成 JSON 格式的目录结构
- **智能缓存机制**（核心功能）

**缓存机制设计**:

#### 1. 缓存文件
- `data/tree.json`: 缓存的目录树结构
- `data/cache_manifest.json`: 缓存元数据

#### 2. 缓存元数据结构
```json
{
  "version": "1.0",
  "generated_at": "2024-01-01T12:00:00Z",
  "hash": "abc123...",
  "expires_at": "2024-01-01T13:00:00Z",
  "ttl": 3600
}
```

#### 3. 自动更新策略
**选择**: TTL (Time To Live) + 文件变更检测

**刷新规则**:
- **定时刷新**: 缓存默认 1 小时过期（可配置）
- **主动刷新**: 访问时检测缓存是否过期，过期则自动重建
- **智能检测**: 比较 `docs/` 目录的最后修改时间，若有变化则强制刷新

#### 4. DirectoryTree.php 核心方法
```php
class DirectoryTree {
    private $cacheDir = 'data/';
    private $cacheFile = 'data/tree.json';
    private $manifestFile = 'data/cache_manifest.json';
    private $cacheTTL = 3600; // 默认 1 小时
    
    public function __construct($cacheTTL = 3600);
    
    // 构建目录树（带缓存逻辑）
    public function build($basePath);
    
    // 获取目录树（优先从缓存读取）
    public function getTree();
    
    // 检查缓存是否有效
    private function isCacheValid();
    
    // 强制刷新缓存
    public function refreshCache();
    
    // 获取缓存信息
    public function getCacheInfo();
    
    // 生成缓存哈希（用于变化检测）
    private function generateHash($tree);
    
    // 转换方法
    public function toJson();
    public function toArray();
}
```

#### 5. 缓存工作流程
```
请求进入
    ↓
检查 tree.json 是否存在
    ↓
┌─ 不存在 ─→ 扫描 docs/ 目录
│                ↓
│            生成目录树
│                ↓
│            保存到 tree.json
│                ↓
│            保存缓存元数据
│                ↓
└────────── 返回目录树
    ↓
存在 → 读取 cache_manifest.json
    ↓
检查缓存是否过期（TTL 检查）
    ↓
┌─ 已过期 ─→ 扫描 docs/ 目录
│                ↓
│            比较文件变更
│                ↓
│            生成新缓存
│                ↓
│            更新缓存文件
│                ↓
└────────── 返回缓存目录树
    ↓
返回目录树
```

#### 6. 性能优化
- **懒加载**: 只在首次访问时生成缓存
- **增量更新**: 可选检测单个文件变化（高级功能）
- **压缩存储**: 缓存文件 gzip 压缩（可选）
- **错误恢复**: 缓存损坏时自动重建

#### 7. 配置选项
```php
// 可在 index.php 或单独配置文件中设置
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600);        // 缓存有效期（秒）
define('CACHE_AUTO_REFRESH', true); // 是否自动刷新
define('CACHE_DIR', __DIR__ . '/data/');
```

#### 8. 缓存失效策略

**多层级失效机制**:

1. **TTL 过期** (Time-based)
   - 默认 1 小时后自动失效
   - 可配置: `define('CACHE_TTL', 3600);`

2. **文件变更检测** (Content-based)
   - 检测 `docs/` 目录的最后修改时间
   - 目录修改时间 > 缓存生成时间 → 强制刷新

3. **手动失效** (Manual)
   - 删除缓存文件强制重建
   - 可通过 URL 参数触发: `?refresh=1`
   - 可选管理页面

**失效检测算法**:
```php
private function shouldRefreshCache() {
    // 1. TTL 检查
    if ($this->isCacheExpired()) {
        return true;
    }
    
    // 2. 目录变更检查
    $docsMtime = $this->getDocsLastModified();
    $cacheMtime = $this->getCacheGeneratedTime();
    
    if ($docsMtime > $cacheMtime) {
        return true;
    }
    
    return false;
}
```

#### 9. 缓存管理工具

**手动刷新方式**:
1. **URL 参数**: 访问 `/?refresh=1` 强制刷新缓存
2. **删除文件**: 删除 `data/tree.json` 和 `data/cache_manifest.json`
3. **CLI 脚本** (可选): `php cache/refresh.php`

**cache/refresh.php 示例**:
```php
<?php
// 强制刷新缓存
require_once __DIR__ . '/../lib/DirectoryTree.php';

$tree = new DirectoryTree();
$result = $tree->refreshCache();

if ($result) {
    echo "缓存已刷新\n";
    echo "生成时间: " . $tree->getCacheInfo()['generated_at'] . "\n";
} else {
    echo "缓存刷新失败\n";
}
?>
```

**输出格式**:
```json
{
  "type": "folder",
  "name": "guide",
  "path": "guide",
  "children": [
    {
      "type": "file",
      "name": "Getting Started",
      "path": "guide/getting-started",
      "file": "guide/getting-started.md"
    }
  ]
}
```

### 2. DocumentParser.php - 文档解析器（含缓存）

**功能**:
- 使用 Parsedown 解析 Markdown
- 提取文档标题（从文件名或第一个 `#` 标题）
- **解析结果缓存**（可选优化）
- 处理相对路径的图片/链接

**缓存策略**（可选）:
- 将解析后的 HTML 内容缓存到 `data/content/` 目录
- 文件名使用 URL-safe 的 MD5 哈希
- 与目录树缓存同步失效

### 3. index.php - 单一入口

**URL 处理逻辑**:
1. 解析 PATH_INFO 获取请求路径
2. 映射到 `docs/` 目录下的文件
3. 生成目录树 JSON
4. 渲染页面

### 4. 前端功能

#### 4.1 侧边栏导航
- 显示完整目录树
- 支持折叠/展开文件夹
- 当前页面高亮
- 平滑滚动

#### 4.2 代码高亮 (Prism.js)
**支持语言**:
- JavaScript, Python, PHP, HTML, CSS
- JSON, YAML, Markdown
- Bash/Shell
- SQL, XML

#### 4.3 数学公式 (KaTeX)
**支持语法**:
- 行内公式: `$E = mc^2$`
- 块级公式: `$$\int_0^\infty e^{-x^2} dx$$`

#### 4.4 全文搜索 (FlexSearch)
- 实时搜索
- 模糊匹配
- 搜索结果高亮
- 键盘快捷键支持（Ctrl/Cmd + K）

## 实现步骤

### 第一阶段：基础架构（1-2 小时）

#### 步骤 1.1: 创建项目结构
- 创建所有目录
- 复制 Parsedown.php 到 `lib/`
- 复制 Prism.js 到 `assets/js/`

#### 步骤 1.2: 实现 DirectoryTree.php（含缓存逻辑）
```php
class DirectoryTree {
    private $cacheDir = 'data/';
    private $cacheFile = 'data/tree.json';
    private $manifestFile = 'data/cache_manifest.json';
    private $cacheTTL = 3600; // 默认 1 小时
    
    public function __construct($cacheTTL = 3600);
    
    // 构建目录树（带缓存逻辑）
    public function build($basePath);
    
    // 获取目录树（优先从缓存读取）
    public function getTree();
    
    // 检查缓存是否有效
    private function isCacheValid();
    
    // 强制刷新缓存
    public function refreshCache();
    
    // 获取缓存信息
    public function getCacheInfo();
    
    // 生成缓存哈希（用于变化检测）
    private function generateHash($tree);
    
    // 转换方法
    public function toJson();
    public function toArray();
}
```

#### 步骤 1.3: 实现 DocumentParser.php
```php
class DocumentParser {
    private $parsedown;
    
    public function __construct();
    public function parse($filePath);
    public function getTitle($content);
    public function extractHeadings($html);
}
```

#### 步骤 1.4: 创建 index.php 入口
- URL 路由逻辑
- 文件路径映射
- 错误处理（404）
- **集成缓存调用**
- **缓存失效检测逻辑**

#### 步骤 1.5: 配置 .htaccess
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L,QSA]
```

### 第二阶段：模板与样式（2-3 小时）

#### 步骤 2.1: 创建 templates/header.php
- HTML 头部
- Meta 标签
- CSS 引入
- KaTeX CSS 引入

#### 步骤 2.2: 创建 templates/sidebar.php
- 目录树容器
- 搜索框
- 折叠按钮

#### 步骤 2.3: 创建 templates/content.php
- 文章容器
- 面包屑导航
- 目录锚点

#### 步骤 2.4: 创建 templates/footer.php
- KaTeX JS 引入
- Prism.js 引入
- 主脚本引入

#### 步骤 2.5: 实现 assets/css/style.css
**设计风格**: Obsidian/现代简约
- 主色调: `#4a5568` (深灰蓝)
- 强调色: `#3182ce` (蓝色)
- 背景色: `#ffffff` (白色)
- 侧边栏宽度: 280px
- 响应式断点: 768px (平板), 480px (手机)

**布局**:
```
┌────────────────────────────────────────┐
│              Header (可选)              │
├──────────┬─────────────────────────────┤
│ Sidebar  │      Main Content           │
│ (固定)   │      (可滚动)                │
│          │                             │
│ - Tree   │      # Title                │
│ - Search │                             │
│          │      Content...             │
└──────────┴─────────────────────────────┘
```

#### 步骤 2.6: 实现 assets/css/markdown.css
- Markdown 内容排版
- 标题样式
- 列表样式
- 表格样式
- 引用块样式
- 图片样式
- 代码块样式

### 第三阶段：前端交互（2-3 小时）

#### 步骤 3.1: 实现 assets/js/main.js
- 目录树渲染
- 当前页面高亮
- 侧边栏折叠（移动端）
- 平滑滚动到锚点

#### 步骤 3.2: 实现 assets/js/search.js
- 搜索索引构建（首次加载时）
- 实时搜索输入
- 搜索结果展示
- 键盘导航支持

#### 步骤 3.3: 集成 Prism.js
- 精简语言支持
- 自定义主题样式
- 自动高亮检测

#### 步骤 3.4: 集成 KaTeX
- CDN 引入 JS
- 行内公式渲染
- 块级公式渲染
- MathJax 兼容模式（备用）

### 第四阶段：测试与优化（1-2 小时）

#### 步骤 4.1: 创建测试文档
- `docs/index.md` - 首页
- `docs/guide/index.md` - 指南目录
- `docs/guide/installation.md` - 安装指南
- `docs/api/index.md` - API 文档

#### 步骤 4.2: 功能测试
- [ ] 目录导航正确显示
- [ ] Markdown 渲染正确
- [ ] 代码高亮正常
- [ ] 数学公式渲染
- [ ] 搜索功能可用
- [ ] 响应式布局正常
- [ ] URL 重写正常
- [ ] 404 错误处理
- [ ] **缓存首次生成正常**
- [ ] **缓存自动刷新正常**（TTL 过期后）
- [ ] **文档变更后缓存更新正常**
- [ ] **缓存文件权限正常**

#### 步骤 4.3: 性能优化
- [x] 目录树缓存
- [ ] 搜索索引本地存储
- [ ] CSS/JS 压缩（可选）
- [ ] 文档解析缓存（可选）

## 部署指南

### 服务器要求
- Apache 2.2+ (启用 mod_rewrite)
- PHP 5.6+ (推荐 PHP 7.x)
- 无需数据库
- 无需 Composer

### 安装步骤
1. 下载项目文件
2. 上传到服务器
3. 设置目录权限:
   ```bash
   chmod 755 docs/
   chmod 755 data/
   chmod 755 cache/
   ```
4. 访问网站根目录（缓存将自动生成）

### 缓存初始化
缓存会在首次访问时自动生成，无需手动操作。

**首次访问流程**:
1. 用户访问任意页面
2. DirectoryTree 检测到无缓存文件
3. 自动扫描 `docs/` 目录
4. 生成目录树并保存到 `data/tree.json`
5. 保存缓存元数据到 `data/cache_manifest.json`
6. 返回目录树给前端

**后续访问流程**:
1. 读取缓存元数据
2. 检查 TTL 是否过期
3. 检查 docs/ 目录是否有变更
4. 条件满足 → 自动刷新缓存
5. 否则 → 直接返回缓存的目录树

### 文档更新
- 直接在 `docs/` 目录添加/修改 Markdown 文件
- 无需重启服务
- 刷新页面即可见更新

### 迁移
- 复制整个项目目录
- 无需安装依赖
- 即插即用

## 技术栈总结

| 组件 | 方案 | 版本 |
|------|------|------|
| 后端框架 | 原生 PHP | 5.6+ |
| Markdown 解析 | Parsedown | 1.7.x |
| 代码高亮 | Prism.js | 1.29.x |
| 数学公式 | KaTeX | 0.16.x |
| 搜索 | FlexSearch | 0.7.x |
| CSS | 原生 CSS | 3.x |
| JavaScript | 原生 JS | ES5+ |

## 文件清单

需要创建的核心文件：
1. `index.php`
2. `.htaccess`
3. `lib/Parsedown.php`
4. `lib/DirectoryTree.php` (含缓存逻辑)
5. `lib/DocumentParser.php`
6. `templates/header.php`
7. `templates/sidebar.php`
8. `templates/content.php`
9. `templates/footer.php`
10. `assets/css/style.css`
11. `assets/css/markdown.css`
12. `assets/js/main.js`
13. `assets/js/search.js`
14. `assets/js/prism.js` (精简版)
15. `cache/refresh.php` (可选：缓存刷新工具)

需要下载的第三方库：
- Parsedown.php
- Prism.js (核心 + 常用语言包)
- KaTeX (CDN 引入)
- FlexSearch (CDN 引入)

## 验收标准

1. ✅ 访问首页正确显示 `docs/index.md` 内容
2. ✅ 侧边栏显示完整的目录树
3. ✅ 点击目录项跳转到对应文档
4. ✅ Markdown 内容正确渲染
5. ✅ 代码块语法高亮正常
6. ✅ 数学公式正确显示
7. ✅ 搜索功能可用
8. ✅ 移动端布局正常
9. ✅ 404 错误正确处理
10. ✅ 无 Composer，依赖直接引入
