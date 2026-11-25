import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Colors and fonts follow notes/DESIGN.md (black/white frame, one green accent, near-square corners).
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
                brand: { DEFAULT: '#76b900', dark: '#5a8d00' }, // green: buttons, active tab, caret
                ink: '#000000',
                body: '#1a1a1a',
                mute: '#757575',
                soft: '#f7f7f7', // surface-soft
                elevated: '#1a1a1a', // dark panels (code, streaming)
                hairline: { DEFAULT: '#cccccc', strong: '#5e5e5e' },
                danger: '#e52020',
                success: '#3f8500', // "berhasil" text on white
                warning: '#b35100',
            },
            fontFamily: {
                sans: ['Inter', 'Arial', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
        },
    },

    plugins: [forms],
};
