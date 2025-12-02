// PM2 Ecosystem Configuration
// For production deployment on CyberPanel/AlmaLinux9

module.exports = {
  apps: [{
    name: 'realtime-chat',
    script: './server/index.js',
    instances: 'max', // Use all available CPU cores
    exec_mode: 'cluster',
    autorestart: true,
    watch: false,
    max_memory_restart: '500M',
    env: {
      NODE_ENV: 'development',
      PORT: 3000
    },
    env_production: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    // Logging
    error_file: './logs/error.log',
    out_file: './logs/out.log',
    log_file: './logs/combined.log',
    time: true,
    // Performance
    node_args: '--max-old-space-size=512',
    // Graceful shutdown
    kill_timeout: 5000,
    wait_ready: true,
    listen_timeout: 10000,
    // Restart policy
    exp_backoff_restart_delay: 100,
    max_restarts: 10,
    min_uptime: '5s'
  }]
};
