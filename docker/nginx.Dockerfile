FROM node:20 as frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM nginx:alpine

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copy public directory for Nginx to serve static files
COPY public /var/www/public
# Copy built frontend assets
COPY --from=frontend /app/public/build /var/www/public/build

WORKDIR /var/www

