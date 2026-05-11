# Markdown Website - PHP 文档网站系统

基于 PHP 的 Markdown 文档网站系统，支持文件夹组织、代码高亮、数学公式和全文搜索。

## 功能特性

- ✅ **文件夹组织** - 按照现有文件夹结构自动生成目录导航
- ✅ **代码高亮** - 支持多种编程语言的语法高亮
- ✅ **数学公式** - 支持 LaTeX 数学公式渲染
- ✅ **全文搜索** - 实时搜索文档内容
- ✅ **目录导航** - 自动生成文档目录
- ✅ **响应式设计** - 适配桌面和移动设备
- ✅ **缓存机制** - 目录树缓存，定期自动更新
- ✅ **无需数据库** - 纯文件存储，零配置部署

## 环境要求

- PHP 5.6+ (推荐 PHP 7.x 或更高版本)
- Apache (需要启用 mod_rewrite) 或 Nginx
- 无需数据库

## 快速开始

### 1. 安装

```bash
# 克隆或下载项目到服务器
git clone <repository-url> /var/www/html/website

# 设置目录权限
chmod 755 docs/
chmod 755 data/
chmod 755 cache/
```

### 2. 配置 Web 服务器

#### Apache

确保 `.htaccess` 文件已启用，mod_rewrite 模块已开启：

```bash
a2enmod rewrite
service apache2 restart
```

#### Nginx

添加以下配置到 Nginx 站点配置：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 3. 访问网站

在浏览器中打开网站地址，例如：`http://localhost/`

系统会自动生成目录树缓存。

## 文档管理

### 添加文档

将 Markdown 文件放入 `docs/` 目录即可：

```
docs/
├── index.md              # 首页
├── guide/
│   ├── index.md          # 指南首页
│   └── getting-started.md
└── api/
    ├── index.md
    └── overview.md
```

### 文档规范

#### 文件命名

- 使用英文或拼音命名文件
- 使用连字符 `-` 分隔单词：`getting-started.md`
- 避免特殊字符

#### 标题

文档的第一个 `#` 标题会自动提取为页面标题：

```markdown
# 页面标题

这是内容...
```

#### 代码块

使用三个反引号包裹代码，并指定语言：

````markdown
```php
<?php
echo "Hello World";
```
````

支持的语言：PHP、JavaScript、Python、Bash、JSON、SQL、YAML、HTML、CSS 等。

#### 数学公式

行内公式使用单个 `$`：

```markdown
这是一个公式 $E = mc^2$ 示例
```

块级公式使用双个 `$$`：

```markdown
$$
\int_0^\infty e^{-x^2} dx = \frac{\sqrt{\pi}}{2}
$$
```

#### 链接

相对路径链接会自动处理：

```markdown
[指南](guide/getting-started)
[API](api/overview)
```

## 配置

### 缓存设置

在 `index.php` 中修改缓存配置：

```php
define('CACHE_ENABLED', true);    // 启用缓存
define('CACHE_TTL', 3600);        // 缓存有效期（秒），默认 1 小时
```

### 缓存管理

#### 自动刷新

缓存会在以下情况自动刷新：
- TTL 过期（默认 1 小时）
- 文档目录有文件变更
- 手动刷新

#### 手动刷新

访问 URL 参数刷新：

```
http://localhost/?refresh=1
```

或使用命令行工具：

```bash
php cache/refresh.php
```

## API 接口

### 目录树 API

获取目录树 JSON：

```
GET /?tree
```

响应示例：

```json
[
  {
    "type": "folder",
    "name": "指南",
    "path": "guide",
    "children": [
      {
        "type": "file",
        "name": "入门指南",
        "path": "guide/getting-started",
        "file": "guide/getting-started.md"
      }
    ]
  }
]
```

## 目录结构

```
website/
├── index.php                 # 单一入口
├── .htaccess                 # Apache URL 重写
│
├── lib/                      # 核心库
│   ├── Parsedown.php         # Markdown 解析器
│   ├── DirectoryTree.php     # 目录树生成器
│   └── DocumentParser.php    # 文档解析器
│
├── docs/                     # Markdown 文档目录
│   ├── index.md
│   ├── guide/
│   └── api/
│
├── templates/                # PHP 模板
│   ├── header.php
│   ├── sidebar.php
│   ├── content.php
│   └── footer.php
│
├── assets/                   # 静态资源
│   ├── css/
│   │   ├── style.css         # 主样式
│   │   └── markdown.css      # Markdown 内容样式
│   └── js/
│       ├── main.js           # 主脚本
│       └── search.js         # 搜索功能
│
├── data/                     # 缓存数据
│   ├── tree.json
│   └── cache_manifest.json
│
└── cache/                    # 缓存工具
    └── refresh.php           # 缓存刷新脚本
```

## 第三方库

本项目使用以下开源库：

- **Parsedown** - Markdown 解析器 (MIT License)
- **Prism.js** - 代码高亮 (MIT License)
- **KaTeX** - 数学公式渲染 (MIT License)
- **FlexSearch** - 全文搜索 (Apache License 2.0)
- **Inter** - 字体 (OFL License)
- **JetBrains Mono** - 代码字体 (OFL License)

## 常见问题

### Q: 网站显示空白页面？

检查以下事项：
1. PHP 是否正常运行：`php -v`
2. 文件权限是否正确：`ls -la docs/`
3. Apache/Nginx 错误日志

### Q: 目录树不显示？

1. 检查 `docs/` 目录是否有 `.md` 文件
2. 手动刷新缓存：`php cache/refresh.php`
3. 检查文件权限

### Q: 搜索功能不工作？

1. 确保浏览器可以加载 CDN 资源
2. 检查 JavaScript 控制台是否有错误
3. 搜索需要至少输入 2 个字符

### Q: 如何禁用缓存？

在 `index.php` 中设置：

```php
define('CACHE_ENABLED', false);
```

## 性能优化

### 建议

1. **启用 OPcache**：在 `php.ini` 中启用
2. **使用 CDN**：将静态资源部署到 CDN
3. **压缩资源**：启用 CSS/JS 压缩
4. **缓存优化**：根据文档更新频率调整缓存 TTL

### 大型文档库

对于超过 1000 个文档的网站，建议：

1. 增加缓存 TTL
2. 启用静态资源长期缓存
3. 考虑使用内存缓存（如 Redis）

## 部署检查清单

- [ ] PHP 版本 >= 5.6
- [ ] Web 服务器已配置
- [ ] `.htaccess` 或 Nginx 配置已设置
- [ ] 目录权限已设置
- [ ] 缓存目录已创建
- [ ] 测试首页可访问
- [ ] 测试导航功能
- [ ] 测试搜索功能
- [ ] 测试代码高亮
- [ ] 测试数学公式（如果有）

## 安全建议

1. **保护 docs/ 目录**：阻止直接访问
   - Apache: `.htaccess` 已配置
   - Nginx: 添加相应规则

2. **限制 PHP 上传**：如果有文件上传功能

3. **定期更新**：保持系统和依赖更新

4. **备份文档**：定期备份 `docs/` 目录

## 许可证

MIT License - 可自由使用、修改和分发

## 技术支持

如有问题，请提交 Issue 或联系维护者。

## 版本历史

### v1.0.0
- 初始版本
- Markdown 解析
- 目录树导航
- 全文搜索
- 代码高亮
- 数学公式支持
- 缓存机制
