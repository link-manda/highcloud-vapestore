/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                'gallery-bg': '#FAFAFA',
                'gallery-surface': '#FFFFFF',
                'gallery-text': '#000000',
                'gallery-muted': '#404040',
                'gallery-dim': '#999999',
                'gallery-border': '#EEEEEE',
            },
            fontFamily: {
                'headline': ['Plus Jakarta Sans', 'sans-serif'],
                'body': ['Manrope', 'sans-serif'],
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/container-queries'),
    ],
};
