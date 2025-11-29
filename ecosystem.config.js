/**
 * PM2 Ecosystem Configuration
 * DentalFlow - 치과 기공소 작업 관리 시스템
 *
 * 사용법:
 * - 시작: pm2 start ecosystem.config.js
 * - 재시작: pm2 restart all
 * - 상태 확인: pm2 status
 * - 로그 확인: pm2 logs
 * - 모니터링: pm2 monit
 */

module.exports = {
  apps: [
    // Next.js 웹 애플리케이션
    {
      name: 'dentalflow-web',
      script: 'node_modules/next/dist/bin/next',
      args: 'start',
      cwd: '/var/www/dentalflow',
      instances: 2, // KVM 2 기준 (2 vCPU)
      exec_mode: 'cluster', // 클러스터 모드로 부하 분산
      env: {
        NODE_ENV: 'production',
        PORT: 3000,
      },
      env_file: '.env.local',
      max_memory_restart: '500M',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      error_file: '/var/log/pm2/dentalflow-error.log',
      out_file: '/var/log/pm2/dentalflow-out.log',
      merge_logs: true,
      // 자동 재시작 설정
      autorestart: true,
      watch: false,
      max_restarts: 10,
      restart_delay: 1000,
      // 헬스체크
      exp_backoff_restart_delay: 100,
    },

    // Socket.io 실시간 서버
    {
      name: 'dentalflow-socket',
      script: 'server/dist/index.js',
      cwd: '/var/www/dentalflow',
      instances: 1,
      exec_mode: 'fork',
      env: {
        NODE_ENV: 'production',
        PORT: 3001,
      },
      env_file: '.env.local',
      max_memory_restart: '200M',
      error_file: '/var/log/pm2/socket-error.log',
      out_file: '/var/log/pm2/socket-out.log',
      merge_logs: true,
      autorestart: true,
      watch: false,
      max_restarts: 10,
      restart_delay: 1000,
    },
  ],

  // 배포 설정 (선택사항)
  deploy: {
    production: {
      user: 'root',
      host: 'your-vps-ip',
      ref: 'origin/main',
      repo: 'git@github.com:your-username/dentalflow.git',
      path: '/var/www/dentalflow',
      'pre-deploy-local': '',
      'post-deploy':
        'npm ci && npm run build && npx prisma migrate deploy && pm2 reload ecosystem.config.js --env production',
      'pre-setup': '',
    },
  },
}
