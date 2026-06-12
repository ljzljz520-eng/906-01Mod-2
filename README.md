# Torrent 搜索引擎

基于 Docker 的现代化 Torrent 搜索引擎，集成多个主流 Torrent 站点，提供统一的搜索接口和美观的 Web 界面。

## 🛠 技术栈

- **前端**: Vue 3 + TailwindCSS + Vite
- **后端**: PHP 8.2 (Vanilla + PDO)
- **数据库**: MySQL 8.0
- **容器化**: Docker + Docker Compose

## ✨ 核心功能

- 🔍 **智能搜索**: 基于 1337x 的全网 Torrent 资源搜索
- ⭐ **收藏管理**: 收藏喜欢的资源，一键复制磁力链接
- 🎨 **现代化 UI**: 基于 TailwindCSS 的美观界面，支持响应式布局
- 📱 **移动端适配**: 完美支持移动设备访问
- 🔄 **演示数据**: 智能降级方案，确保系统功能可展示

## 🚀 快速开始

### 前置要求

- Docker Desktop (确保已安装并启动)

### 一键启动

```bash
# 克隆项目后，在项目根目录执行
docker compose up --build

# 等待容器启动完成（首次启动约需 3-5 分钟）
```

## 🔗 服务地址

- **前端界面**: http://localhost:3003
- **后端 API**: http://localhost:3002
- **数据库**: localhost:3307

## 🗄️ 数据库信息

- **主机**: localhost
- **端口**: 3307
- **用户名**: baobei
- **密码**: 123456
- **数据库**: torrent_search

**命令行连接**:
```bash
mysql -h127.0.0.1 -P3307 -ubaobei -p123456 torrent_search
```

**Docker 连接**:
```bash
docker exec -it torrent-db mysql -ubaobei -p123456 torrent_search
```


## 🛑 停止服务

```bash
# 停止服务
docker compose down

# 停止服务并删除数据卷
docker compose down -v
```

## 🏰 宝塔面板 (BT Panel) 部署指南

### 1. 环境准备
- 确保宝塔面板已安装 **Docker管理器** 插件
- 确保服务器已安装 Docker Compose (宝塔 Docker 管理器通常自带，或需手动升级)

### 2. 上传代码
1. 在宝塔文件管理中，进入 `/www/wwwroot` 目录
2. 创建新文件夹 `torrent-search`
3. 将本项目所有文件（包括 `backend-php/`, `frontend/`, `database/`, `docker-compose.yml` 等）上传到该目录

### 3. 开始部署
1. 打开宝塔终端
2. 进入项目目录：
   ```bash
   cd /www/wwwroot/torrent-search
   ```
3. 执行启动命令：
   ```bash
   docker compose up --build -d
   ```
4. 等待容器启动完成。

### 4. 访问配置
- **默认端口**：
  - 前端：`3003`
  - 后端：`3002`

- **反向代理 (推荐)**：
  为了通过域名访问（如 `search.example.com`），请在宝塔"网站"中添加站点：
  1. 添加一个静态站点（域名 `search.example.com`）
  2. 进入网站设置 -> **反向代理**
  3. 添加反代：
     - 目标URL: `http://127.0.0.1:3003`
     - 发送域名: `$host`
  4. 此时即可通过域名直接访问。

## 📦 项目结构

```
.
├── backend-php/            # 后端服务 (PHP)
│   ├── models/            # 数据库模型
│   ├── controllers/       # 控制器
│   ├── public/            # 入口文件
│   └── config/            # 配置文件
├── frontend/              # 前端服务
│   ├── src/
│   │   ├── views/        # 页面组件
│   │   └── App.vue       # 根组件
│   └── nginx.conf        # Nginx 配置
├── database/             # 数据库脚本
│   └── init.sql         # 初始化脚本
└── docker-compose.yml   # Docker 编排配置
```

## 📝 注意事项

- 本项目仅供学习交流使用
- 搜索结果来自公开的 Torrent 站点
- 由于第三方 API 限制，当前使用演示数据展示功能

## 📄 开源协议

MIT License

---

**开发时间**: 2026-01-25
**支持的搜索源**: 1337x (固定)
