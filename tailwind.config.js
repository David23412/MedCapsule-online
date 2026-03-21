/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.ts",
    "./resources/**/*.tsx",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        plasma: '#418BF5',
        ghost: '#F0EFF4',
        charcoal: '#1A1A1A',
        'charcoal-dim': '#4A4A52',
      },
      fontFamily: {
        heading: ['"Sora"', 'sans-serif'],
        drama: ['"Instrument Serif"', 'serif'],
        mono: ['"Fira Code"', 'monospace'],
        body: ['"Plus Jakarta Sans"', 'sans-serif'],
      },
      boxShadow: {
        'clinical': '0 25px 50px -12px rgba(30, 58, 138, 0.05)',
      }
    },
  },
  plugins: [],
}
