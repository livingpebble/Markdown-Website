# Markdown 文档网站项目计划

## 项目概述
将文件夹中的 Markdown 文档按照现有组织结构转换为 PHP 网站，支持在左侧显示文件夹目录导航，无需数据库，纯静态文档更新方式。

## 当前状态分析
- **工作区状态**: 空目录（仅有一个 README.md）
- **需求来源**: 需要创建一个全新的 PHP 网站项目
- **核心功能**: Markdown 解析 + 目录导航 + 页面渲染

## 技术方案决策

### 1. PHP Markdown 解析方案
**选择**: 使用 `Parsedown` 库
- 原因: 轻量级、PHP 5.6 完全兼容、广泛使用、稳定可靠
- 备选: 纯正则实现（过于复杂，不推荐）

### 2. URL 路由方案
**选择**: 单一入口 `index.php` + PATH_INFO
- 原因: PHP 5.6 兼容、简单可靠、无需额外配置
- 示例: `/index.php/docs/folder/page` 或 `.htaccess` 重写为 `/docs/folder/page`

### 3. 目录结构方案
```
docs/                  # Markdown 文档根目录
  ├── index.md         # 首页
  ├── folder1/
  │   ├── index.md     # 文件夹首页
  │   ├── page1.md
  │   └── subfolder/
  │       └── page2.md
  └── folder2/
      └── page3.md

public/                # 网站根目录
  ├── index.php        # 单一入口
  ├── .htaccess         # Apache URL 重写
  ├── assets/
  │   ├── css/
  │   │   └── style.css
  │   └── js/
  │       └── main.js
  └── vendor/           # 第三方库
      └── parsedown/
          └── Parsedown.php

lib/                   # 核心库
  ├── DocumentParser.php
  └── DirectoryTree.php

templates/              # 模板文件
  ├── header.php
  ├── sidebar.php
  └── footer.php
```

### 4. 目录导航构建方案
- 递归扫描 `docs/` 目录
- 生成 JSON 格式的目录树
- 前端 JavaScript 动态渲染侧边栏
- 支持折叠/展开文件夹

### 5. 样式方案
**风格**: Obsidian/现代简约风格
- 左侧固定侧边栏（可折叠）
- 右侧主内容区域
- 简洁的 Markdown 渲染样式
- 代码高亮（使用 Prism.js）
- 响应式设计

## 实现步骤

### 第一步：项目结构初始化
- 创建目录结构
- 创建 `composer.json` (可选，或直接引入 Parsedown)
- 创建 `.htaccess` 进行 URL 重写

### 第二步：核心功能开发

#### 2.1 DirectoryTree.php - 目录树生成器
- `buildTree($path)`: 递归扫描目录
- `toJson()`: 转换为 JSON
- 功能: 生成侧边栏所需的目录结构

#### 2.2 DocumentParser.php - 文档解析器
- `parse($filePath)`: 解析 Markdown 文件
- `getMetadata()`: 提取 YAML front matter (可选)
- 功能: 使用 Parsedown 转换为 HTML

#### 2.3 index.php - 单一入口
- 解析 URL PATH_INFO
- 映射到 docs/ 目录下的文件
- 渲染页面

### 第三步：前端实现

#### 3.1 templates/header.php
- HTML 头部
- CSS/JS 引入
- 响应式布局容器

#### 3.2 templates/sidebar.php
- 目录树容器
- 折叠/展开功能
- 当前页面高亮

#### 3.3 templates/footer.php
- 页脚内容
- JavaScript 逻辑

#### 3.4 assets/css/style.css
- 整体布局
- 侧边栏样式
- Markdown 内容样式
- 代码高亮样式
- 响应式适配

#### 3.5 assets/js/main.js
- 目录树渲染
- 折叠/展开逻辑
- 当前页面激活状态

### 第四步：高级功能（可选）

#### 4.1 代码高亮
- 集成 Prism.js 或 highlight.js
- 支持常见编程语言

#### 4.2 数学公式
- 集成 KaTeX 或 MathJax

#### 4.3 搜索功能
- 前端搜索（可选）

## 兼容性保证
- **PHP 版本**: 5.6+ 兼容
- **服务器**: Apache (需要 mod_rewrite)
- **无数据库依赖**
- **前端**: 纯 HTML/CSS/JS，无需构建工具

## 部署说明
1. 将所有文件上传到服务器
2. 确保 Apache 启用 mod_rewrite
3. 将 Markdown 文档放入 `docs/` 目录
4. 访问网站根目录即可

## 验证步骤
1. 创建测试 Markdown 文件
2. 验证目录导航显示正确
3. 验证 Markdown 渲染正确
4. 验证代码高亮功能
5. 测试响应式布局
6. 测试 URL 重写

## 依赖项
- Parsedown: https://github.com/erusev/parsedown
- Prism.js (代码高亮): https://prismjs.com/
- Font Awesome 或自定义图标

## 注意事项
- 所有路径使用相对路径或绝对路径
- 文件名中的空格需要 URL 编码处理
- 中文文件名支持
- 大型目录需要考虑性能优化
