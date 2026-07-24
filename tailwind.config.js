import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                ink: {
                    DEFAULT: '#0B0B0F',
                    50: '#F5F5F7',
                    100: '#E8E8ED',
                    200: '#D1D1D6',
                    300: '#AEAEB2',
                    400: '#86868B',
                    500: '#636366',
                    600: '#48484A',
                    700: '#2C2C2E',
                    800: '#1C1C1E',
                    900: '#0B0B0F',
                    950: '#050507',
                },
                surface: {
                    DEFAULT: '#FFFFFF',
                    subtle: '#FAFAFA',
                    muted: '#F5F5F7',
                    inverse: '#0B0B0F',
                },
                accent: {
                    DEFAULT: '#0071E3',
                    hover: '#0077ED',
                    soft: '#E8F2FF',
                    dark: '#005BB5',
                },
                commerce: {
                    sale: '#E11900',
                    'sale-soft': '#FFF0EE',
                    new: '#111111',
                    'new-soft': '#F5F5F7',
                },
                success: {
                    DEFAULT: '#0A7A44',
                    soft: '#ECFDF3',
                    border: '#ABEFC6',
                },
                warning: {
                    DEFAULT: '#B45309',
                    soft: '#FFFBEB',
                    border: '#FDE68A',
                },
                danger: {
                    DEFAULT: '#DC2626',
                    soft: '#FEF2F2',
                    border: '#FECACA',
                },
                info: {
                    DEFAULT: '#0369A1',
                    soft: '#F0F9FF',
                    border: '#BAE6FD',
                },
            },

            fontFamily: {
                sans: [
                    'Instrument Sans',
                    'Inter',
                    'SF Pro Display',
                    'SF Pro Text',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    'Segoe UI',
                    'Roboto',
                    ...defaultTheme.fontFamily.sans,
                ],
                display: [
                    'Instrument Sans',
                    'Inter',
                    'SF Pro Display',
                    ...defaultTheme.fontFamily.sans,
                ],
                mono: [
                    'SF Mono',
                    'JetBrains Mono',
                    'Fira Code',
                    ...defaultTheme.fontFamily.mono,
                ],
            },

            fontSize: {
                '2xs': ['0.6875rem', { lineHeight: '1rem', letterSpacing: '0.02em' }],
                xs: ['0.75rem', { lineHeight: '1.125rem', letterSpacing: '0.01em' }],
                sm: ['0.875rem', { lineHeight: '1.375rem', letterSpacing: '0.005em' }],
                base: ['1rem', { lineHeight: '1.625rem', letterSpacing: '0' }],
                lg: ['1.125rem', { lineHeight: '1.75rem', letterSpacing: '-0.005em' }],
                xl: ['1.25rem', { lineHeight: '1.875rem', letterSpacing: '-0.01em' }],
                '2xl': ['1.5rem', { lineHeight: '2rem', letterSpacing: '-0.015em' }],
                '3xl': ['1.875rem', { lineHeight: '2.25rem', letterSpacing: '-0.02em' }],
                '4xl': ['2.25rem', { lineHeight: '2.5rem', letterSpacing: '-0.025em' }],
                '5xl': ['3rem', { lineHeight: '1.1', letterSpacing: '-0.03em' }],
                '6xl': ['3.75rem', { lineHeight: '1.05', letterSpacing: '-0.035em' }],
            },

            spacing: {
                4.5: '1.125rem',
                13: '3.25rem',
                15: '3.75rem',
                18: '4.5rem',
                22: '5.5rem',
                30: '7.5rem',
            },

            maxWidth: {
                prose: '68ch',
                content: '72rem',
                wide: '90rem',
            },

            borderRadius: {
                ds: '0.75rem',
                'ds-lg': '1rem',
                'ds-xl': '1.25rem',
                pill: '9999px',
            },

            boxShadow: {
                'ds-xs': '0 1px 2px 0 rgb(11 11 15 / 0.04)',
                'ds-sm': '0 1px 3px 0 rgb(11 11 15 / 0.06), 0 1px 2px -1px rgb(11 11 15 / 0.06)',
                'ds-md': '0 4px 12px -2px rgb(11 11 15 / 0.08), 0 2px 6px -2px rgb(11 11 15 / 0.05)',
                'ds-lg': '0 12px 32px -8px rgb(11 11 15 / 0.12), 0 4px 12px -4px rgb(11 11 15 / 0.06)',
                'ds-xl': '0 24px 48px -12px rgb(11 11 15 / 0.16)',
                'ds-inner': 'inset 0 1px 0 0 rgb(255 255 255 / 0.06)',
                'ds-glow': '0 0 0 1px rgb(0 113 227 / 0.15), 0 8px 24px -8px rgb(0 113 227 / 0.35)',
            },

            transitionTimingFunction: {
                'ds-out': 'cubic-bezier(0.22, 1, 0.36, 1)',
                'ds-spring': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
            },

            transitionDuration: {
                250: '250ms',
                350: '350ms',
            },

            animation: {
                'ds-shimmer': 'ds-shimmer 1.6s ease-in-out infinite',
                'ds-fade-in': 'ds-fade-in 350ms cubic-bezier(0.22, 1, 0.36, 1) both',
                'ds-slide-up': 'ds-slide-up 450ms cubic-bezier(0.22, 1, 0.36, 1) both',
                'ds-pulse-soft': 'ds-pulse-soft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },

            keyframes: {
                'ds-shimmer': {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                'ds-fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'ds-slide-up': {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'ds-pulse-soft': {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.55' },
                },
            },

            backgroundImage: {
                'ds-shimmer-gradient':
                    'linear-gradient(90deg, rgb(245 245 247 / 0) 0%, rgb(255 255 255 / 0.65) 50%, rgb(245 245 247 / 0) 100%)',
            },
        },
    },

    plugins: [
        forms({
            strategy: 'class',
        }),
    ],
};
