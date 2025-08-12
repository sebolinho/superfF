import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import scrollbar from 'tailwind-scrollbar';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        // Caminhos essenciais e otimizados para um projeto Laravel.
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',

        // Caminho para as views de paginação do Laravel.
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',

        // Se você utiliza componentes de Blade anônimos, adicione este caminho.
        // './app/View/Components/**/*.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            container: {
                center: true,
                padding: {
                    DEFAULT: '1.5rem',
                },
            },
            screens: {
                'sm': '640px',
                'md': '768px',
                'lg': '1024px',
                'xl': '1280px',
                '2xl': '1536px',
                '3xl': '1930px',
            },
            letterSpacing: {
                tighter: '-.015em',
                tight: '-.010rem',
                normal: '0',
                wide: '.025em',
                wider: '.05em',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                xxs: '10px'
            },
            aspectRatio: {
                auto: 'auto',
                poster: '2 / 3',
                square: '1 / 1',
                video: '16 / 9',
                slide: '3 / 1'
            },
            colors: {
                primary: {
                    50: "rgb(var(--color-primary-50) / <alpha-value>)",
                    100: "rgb(var(--color-primary-100) / <alpha-value>)",
                    200: "rgb(var(--color-primary-200) / <alpha-value>)",
                    300: "rgb(var(--color-primary-300) / <alpha-value>)",
                    400: "rgb(var(--color-primary-400) / <alpha-value>)",
                    500: "rgb(var(--color-primary-500) / <alpha-value>)",
                    600: "rgb(var(--color-primary-600) / <alpha-value>)",
                    700: "rgb(var(--color-primary-700) / <alpha-value>)",
                    800: "rgb(var(--color-primary-800) / <alpha-value>)",
                    900: "rgb(var(--color-primary-900) / <alpha-value>)",
                },
                gray: {
                    50: "rgb(var(--color-gray-50) / <alpha-value>)",
                    100: "rgb(var(--color-gray-100) / <alpha-value>)",
                    200: "rgb(var(--color-gray-200) / <alpha-value>)",
                    300: "rgb(var(--color-gray-300) / <alpha-value>)",
                    400: "rgb(var(--color-gray-400) / <alpha-value>)",
                    500: "rgb(var(--color-gray-500) / <alpha-value>)",
                    600: "rgb(var(--color-gray-600) / <alpha-value>)",
                    700: "rgb(var(--color-gray-700) / <alpha-value>)",
                    800: "rgb(var(--color-gray-800) / <alpha-value>)",
                    900: "rgb(var(--color-gray-900) / <alpha-value>)",
                    950: "rgb(var(--color-gray-950) / <alpha-value>)",
                },
            },
            keyframes: {
                loading: {
                    '0%': { left: '-100%' },
                    '50%': { left: '100%' },
                    '100%': { left: '-100%' },
                },
            },
            animation: {
                loading: 'loading 2s linear infinite',
            },
            backgroundImage: {
                'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
            }
        },
    },

    plugins: [
        forms,
        typography,
        scrollbar({ nocompatible: true }),
    ],
};