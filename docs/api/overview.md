# API 概览

本系统提供完整的 RESTful API 接口。

## 基本信息

- **基础 URL**: `https://api.example.com/v1`
- **数据格式**: JSON
- **认证方式**: API Key

## 认证

所有 API 请求需要在 Header 中包含 API Key：

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://api.example.com/v1/documents
```

## 接口列表

### 文档接口

- `GET /documents` - 获取文档列表
- `GET /documents/:id` - 获取单个文档
- `POST /documents` - 创建新文档
- `PUT /documents/:id` - 更新文档
- `DELETE /documents/:id` - 删除文档

### 搜索接口

- `GET /search?q=keyword` - 搜索文档

## 请求示例

### 获取文档列表

```bash
curl -X GET "https://api.example.com/v1/documents" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

响应示例：

```json
{
  "success": true,
  "data": [
    {
      "id": "doc_123",
      "title": "API 概览",
      "path": "/api/overview",
      "created_at": "2024-01-01T12:00:00Z"
    }
  ]
}
```

### 创建文档

```bash
curl -X POST "https://api.example.com/v1/documents" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "新文档",
    "content": "# 新文档内容",
    "path": "/new-document"
  }'
```

## 错误处理

所有错误响应都遵循以下格式：

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "错误描述"
  }
}
```

常见错误代码：

- `400` - 请求参数错误
- `401` - 认证失败
- `404` - 资源不存在
- `500` - 服务器内部错误
