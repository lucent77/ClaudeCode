/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./public/**/*.{html,js}"],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Slack-inspired color palette
        'slack-purple': '#4A154B',
        'slack-purple-light': '#611f69',
        'slack-purple-dark': '#350d36',
        'slack-green': '#2EB67D',
        'slack-blue': '#36C5F0',
        'slack-yellow': '#ECB22E',
        'slack-red': '#E01E5A',
        // Dark theme colors
        'dark-bg': '#1a1d21',
        'dark-sidebar': '#19171d',
        'dark-hover': '#27242c',
        'dark-active': '#1164A3',
        'dark-border': '#565856',
        'dark-text': '#d1d2d3',
        'dark-text-muted': '#ababad',
      },
      fontFamily: {
        'slack': ['Lato', 'Slack-Lato', 'appleLogo', 'sans-serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
        'slide-in': 'slideIn 0.3s ease-out',
        'pulse-soft': 'pulseSoft 2s infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideIn: {
          '0%': { transform: 'translateX(-10px)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
        pulseSoft: {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.7' },
        },
      },
    },
  },
  plugins: [],
}
