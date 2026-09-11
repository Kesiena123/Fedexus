/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                navy: {
                    50: '#f3f5fb',
                    100: '#e4e9f5',
                    200: '#c3cde6',
                    300: '#94a6d0',
                    400: '#5d76b0',
                    500: '#3a5494',
                    600: '#293e74',
                    700: '#1e2f5c',
                    800: '#16244a',
                    900: '#0e1a3a',
                    950: '#070f24',
                },
                azure: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#5b9bff',
                    500: '#1463ff',
                    600: '#0b4fd1',
                    700: '#0a40a8',
                    800: '#0b357f',
                    900: '#0d2c63',
                },
                gold: {
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f5a623',
                    600: '#d98e07',
                    700: '#b4730a',
                },
                brand: {
                    ink: '#0f172a',
                    purple: '#0e1a3a',
                    orange: '#1463ff',
                    blue: '#0b4fd1',
                    green: '#059669',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                display: ['Space Grotesk', 'Inter', 'ui-sans-serif', 'sans-serif'],
            },
            borderRadius: {
                xl: '0.875rem',
                '2xl': '1.25rem',
                '3xl': '1.75rem',
            },
            boxShadow: {
                enterprise: '0 2px 12px rgba(14, 26, 58, 0.12)',
                card: '0 1px 2px rgba(14, 26, 58, 0.06), 0 8px 24px -12px rgba(14, 26, 58, 0.18)',
                lift: '0 18px 48px -18px rgba(14, 26, 58, 0.35)',
                glow: '0 0 0 1px rgba(20, 99, 255, 0.18), 0 12px 40px -12px rgba(20, 99, 255, 0.45)',
            },
            backgroundImage: {
                'hero-radial': 'radial-gradient(1200px 600px at 80% -10%, rgba(20,99,255,0.35), transparent 60%), radial-gradient(900px 500px at 0% 110%, rgba(245,166,35,0.18), transparent 55%)',
                'azure-gradient': 'linear-gradient(135deg, #1463ff 0%, #0b4fd1 100%)',
                'navy-gradient': 'linear-gradient(160deg, #0e1a3a 0%, #16244a 55%, #0d2c63 100%)',
                'grid-faint': 'linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                marquee: {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.6s cubic-bezier(0.22, 1, 0.36, 1) both',
                float: 'float 6s ease-in-out infinite',
                marquee: 'marquee 30s linear infinite',
                shimmer: 'shimmer 2.2s linear infinite',
            },
        },
    },
    plugins: [],
};
