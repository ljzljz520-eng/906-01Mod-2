# 构建阶段
FROM node:20-alpine AS builder

WORKDIR /app

# 复制 package 文件
COPY package*.json ./

# 配置 npm 镜像源并安装依赖
RUN npm config set registry https://registry.npmmirror.com && \
    npm install

# 复制应用代码
COPY . .

# 构建生产版本
RUN npm run build

# 生产阶段 - 使用 Nginx
FROM nginx:alpine

# 复制构建产物
COPY --from=builder /app/dist /usr/share/nginx/html

# 复制 Nginx 配置
COPY nginx.conf /etc/nginx/conf.d/default.conf

# 暴露端口
EXPOSE 3000

CMD ["nginx", "-g", "daemon off;"]
