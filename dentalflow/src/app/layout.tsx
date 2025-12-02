/**
 * Root Layout - DentalFlow
 *
 * Main application layout with:
 * - Font configuration (Inter)
 * - Global CSS import
 * - Toast notifications (Sonner)
 * - Session provider for Auth.js
 */

import type { Metadata, Viewport } from 'next'
import { Inter } from 'next/font/google'
import { Toaster } from 'sonner'
import './globals.css'

const inter = Inter({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-inter',
})

export const metadata: Metadata = {
  title: {
    default: 'DentalFlow - CADCAM Management System',
    template: '%s | DentalFlow',
  },
  description:
    'Integrated CADCAM workflow management system for dental laboratories. Real-time tracking, multi-department coordination, and efficient case management.',
  keywords: [
    'dental laboratory',
    'CADCAM',
    'CAD/CAM',
    'dental workflow',
    'case management',
    'dental prosthetics',
    'zirconia',
    'dental milling',
  ],
  authors: [{ name: 'DentalFlow Team' }],
  creator: 'DentalFlow',
  publisher: 'DentalFlow',
  robots: {
    index: false,
    follow: false,
  },
  icons: {
    icon: '/favicon.ico',
    apple: '/apple-touch-icon.png',
  },
}

export const viewport: Viewport = {
  width: 'device-width',
  initialScale: 1,
  maximumScale: 1,
  themeColor: [
    { media: '(prefers-color-scheme: light)', color: '#ffffff' },
    { media: '(prefers-color-scheme: dark)', color: '#0a0a0a' },
  ],
}

interface RootLayoutProps {
  children: React.ReactNode
}

export default function RootLayout({ children }: RootLayoutProps) {
  return (
    <html lang="en" className={inter.variable} suppressHydrationWarning>
      <body className="min-h-screen bg-background font-sans antialiased">
        {children}
        <Toaster
          position="top-right"
          toastOptions={{
            duration: 4000,
            classNames: {
              toast: 'group toast bg-background border-border',
              title: 'text-foreground font-semibold',
              description: 'text-muted-foreground',
              actionButton: 'bg-primary text-primary-foreground',
              cancelButton: 'bg-muted text-muted-foreground',
              error: 'bg-destructive text-destructive-foreground border-destructive',
              success: 'bg-emerald-500 text-white border-emerald-600',
              warning: 'bg-amber-500 text-white border-amber-600',
              info: 'bg-blue-500 text-white border-blue-600',
            },
          }}
        />
      </body>
    </html>
  )
}
