/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.php', './app/**/*.php', './components/**/*.php', './assets/footer/*.html'],
  safelist: ['ball-1', 'ball-2', 'ball-3'],
  theme: {
    extend: {
      colors: {
        // `darkbg` y `neon-purple` tenían dos valores distintos según la
        // página. Se resuelven con variables CSS para que una sola hoja de
        // estilos conserve el aspecto de cada sección.
        darkbg:         'rgb(var(--c-darkbg) / <alpha-value>)',
        'dark-bg':      'rgb(var(--c-darkbg) / <alpha-value>)',
        'neon-purple':  'rgb(var(--c-neon-purple) / <alpha-value>)',

        'card-bg':   '#1e293b',
        'card-old':  '#1e293b',
        'neon-teal': '#2dd4bf',
        'neon-cyan': '#22d3ee',
        'neon-pink': '#ec4899',

        neon: { teal: '#2dd4bf', cyan: '#22d3ee' },
        gold: { 300: '#fde047', 400: '#facc15', 500: '#eab308' },
      },
      fontFamily: {
        sans:    ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['"Plus Jakarta Sans"', 'Inter', 'ui-sans-serif', 'sans-serif'],
        mono:    ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
      },
      animation: {
        blob:         'blob 7s infinite',
        'fade-in-up': 'fadeInUp 0.5s ease-out forwards',
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'pulse-fast': 'pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        matrix:       'matrix-anim 2s linear infinite',
      },
      keyframes: {
        blob: {
          '0%':   { transform: 'translate(0px, 0px) scale(1)' },
          '33%':  { transform: 'translate(30px, -50px) scale(1.1)' },
          '66%':  { transform: 'translate(-20px, 20px) scale(0.9)' },
          '100%': { transform: 'translate(0px, 0px) scale(1)' },
        },
        fadeInUp: {
          '0%':   { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        'matrix-anim': {
          '0%':   { backgroundPosition: '0% 0%' },
          '100%': { backgroundPosition: '0% 100%' },
        },
      },
    },
  },
  plugins: [],
}
