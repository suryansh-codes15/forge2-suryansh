/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      colors: {
        bg: {
          DEFAULT: 'hsl(225, 25%, 6%)',
          panel:   'hsl(225, 22%, 10%)',
          card:    'hsl(225, 20%, 13%)',
          input:   'hsl(225, 18%, 16%)',
        },
        border: {
          DEFAULT: 'hsl(225, 18%, 22%)',
          hover:   'hsl(225, 25%, 35%)',
        },
        accent: {
          DEFAULT: 'hsl(250, 80%, 65%)',
          dim:     'hsl(250, 60%, 45%)',
        },
        muted:   'hsl(220, 12%, 55%)',
        faint:   'hsl(220, 10%, 38%)',
        status: {
          open:     'hsl(145, 65%, 50%)',
          pending:  'hsl(210, 85%, 60%)',
          resolved: 'hsl(250, 80%, 65%)',
          closed:   'hsl(220, 12%, 55%)',
          urgent:   'hsl(0, 75%, 60%)',
          high:     'hsl(30, 90%, 55%)',
          medium:   'hsl(45, 92%, 55%)',
          low:      'hsl(145, 65%, 50%)',
        },
      },
    },
  },
  plugins: [],
}
