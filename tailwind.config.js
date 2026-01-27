/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                'uclv-blue': '#0066cc',
                'uclv-light-blue': '#4d94ff',
            },
        },
    },
    plugins: [],
}