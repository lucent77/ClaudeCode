/**
 * PM2 Ecosystem Configuration - DentalFlow
 *
 * Optimized for CyberPanel + OpenLiteSpeed + AlmaLinux 9
 *
 * Processes:
 * - dentalflow-web: Next.js application (Port 3000, cluster mode)
 * - dentalflow-socket: Socket.io server (Port 3001, fork mode)
 *
 * Usage:
 *   pm2 start ecosystem.config.js
 *   pm2 start ecosystem.config.js --env production
 */

module.exports = {
  apps: [
    {
      // Next.js Web Application
      name: 'dentalflow-web',
      script: 'node_modules/next/dist/bin/next',
      args: 'start',
      cwd: '/home/dentalflow',
      instances: 2, // Cluster mode with 2 instances
      exec_mode: 'cluster',
      watch: false,
      max_memory_restart: '500M',
      env: {
        NODE_ENV: 'development',
        PORT: 3000,
      },
      env_production: {
        NODE_ENV: 'production',
        PORT: 3000,
      },
      // Logging
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      error_file: '/home/dentalflow/logs/web-error.log',
      out_file: '/home/dentalflow/logs/web-out.log',
      merge_logs: true,
      // Restart settings
      autorestart: true,
      max_restarts: 10,
      restart_delay: 1000,
      exp_backoff_restart_delay: 100,
      // Health check
      listen_timeout: 10000,
      kill_timeout: 5000,
    },
    {
      // Socket.io Real-time Server
      name: 'dentalflow-socket',
      script: 'dist/server/index.js', // Compiled from TypeScript
      cwd: '/home/dentalflow',
      instances: 1, // Fork mode for WebSocket
      exec_mode: 'fork',
      watch: false,
      max_memory_restart: '300M',
      env: {
        NODE_ENV: 'development',
        PORT: 3001,
        CORS_ORIGIN: 'http://localhost:3000',
      },
      env_production: {
        NODE_ENV: 'production',
        PORT: 3001,
        CORS_ORIGIN: 'https://your-domain.com',
      },
      // Logging
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      error_file: '/home/dentalflow/logs/socket-error.log',
      out_file: '/home/dentalflow/logs/socket-out.log',
      merge_logs: true,
      // Restart settings
      autorestart: true,
      max_restarts: 10,
      restart_delay: 1000,
      exp_backoff_restart_delay: 100,
      // Health check
      listen_timeout: 10000,
      kill_timeout: 5000,
    },
  ],

  // Deployment configuration for pm2 deploy
  deploy: {
    production: {
      user: 'root',
      host: ['your-server-ip'],
      ref: 'origin/main',
      repo: 'git@github.com:your-repo/dentalflow.git',
      path: '/home/dentalflow',
      'pre-deploy-local': '',
      'post-deploy':
        'npm ci && npm run build && npm run socket:build && npx prisma migrate deploy && pm2 reload ecosystem.config.js --env production',
      'pre-setup': '',
      ssh_options: 'StrictHostKeyChecking=no',
    },
    staging: {
      user: 'root',
      host: ['your-staging-ip'],
      ref: 'origin/develop',
      repo: 'git@github.com:your-repo/dentalflow.git',
      path: '/home/dentalflow-staging',
      'post-deploy':
        'npm ci && npm run build && npm run socket:build && npx prisma migrate deploy && pm2 reload ecosystem.config.js',
    },
  },
}
