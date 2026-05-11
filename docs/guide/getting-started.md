# 入门指南

欢迎开始使用本系统！本指南将帮助您快速上手。

## 安装

### 环境要求

- PHP 5.6 或更高版本
- Apache 服务器（启用 mod_rewrite）
- 无需数据库

### 快速安装

1. 下载项目文件到服务器
2. 设置目录权限:
   ```bash
   chmod 755 docs/
   chmod 755 data/
   ```
3. 访问网站开始使用

## 添加文档

### 基本规则

1. 将 Markdown 文件放入 `docs/` 目录
2. 支持多级文件夹组织
3. 每个文件夹可以有 `index.md` 作为首页

### 示例结构

```
docs/
├── index.md
├── guide/
│   ├── index.md
│   └── getting-started.md
└── api/
    ├── index.md
    └── overview.md
```

## Markdown 语法

### 标题

```markdown
# 一级标题
## 二级标题
### 三级标题
```

### 代码块

```php
<?php
echo "Hello World";
?>
```

### 列表

- 无序列表项 1
- 无序列表项 2
  - 嵌套列表项

1. 有序列表项 1
2. 有序列表项 2

### 链接和图片

[访问首页](/)

![示例图片](/path/to/image.png)

### 表格

| 列1 | 列2 | 列3 |
|-----|-----|-----|
| 数据1 | 数据2 | 数据3 |
| 数据4 | 数据5 | 数据6 |

## 数学公式

使用 LaTeX 语法：

行内公式: `$x^2 + y^2 = z^2$`

块级公式:

$$
\sum_{i=1}^{n} x_i = x_1 + x_2 + \cdots + x_n
$$

## 下一步

- 查看 [API 文档](../api/overview)
- 了解高级配置选项
