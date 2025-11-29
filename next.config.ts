import type { NextConfig } from 'next'

const nextConfig: NextConfig = {
  // Enable React strict mode for development
  reactStrictMode: true,

  // Turbopack is now stable in Next.js 15
  // Use `next dev --turbopack` for development

  // Experimental features
  experimental: {
    // Server Actions are stable in Next.js 15
    serverActions: {
      bodySizeLimit: '2mb',
    },
  },

  // Environment variables that should be available on the client
  env: {
    NEXT_PUBLIC_SOCKET_URL: process.env.NEXT_PUBLIC_SOCKET_URL,
  },

  // Image optimization configuration
  images: {
    remotePatterns: [],
  },

  // Headers for security
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          {
            key: 'X-Frame-Options',
            value: 'DENY',
          },
          {
            key: 'X-Content-Type-Options',
            value: 'nosniff',
          },
          {
            key: 'Referrer-Policy',
            value: 'strict-origin-when-cross-origin',
          },
        ],
      },
    ]
  },
}

export default nextConfig
