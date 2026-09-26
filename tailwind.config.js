/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                // Brand primary: vampire blood. 600 is the brand colour itself
                // (#6B0A14, same as the sign-in page); 500 is the brighter
                // "arterial" red used for focus rings.
                primary: {
                    50: '#FBF1F2',
                    100: '#F5E0E2',
                    200: '#EAC0C4',
                    300: '#D8929A',
                    400: '#BF4F5B',
                    500: '#A3121F',
                    600: '#6B0A14',
                    700: '#56070F',
                    800: '#42050C',
                    900: '#330409',
                    950: '#2A0307',
                },
                bone: '#F3ECEC',   // page background, text on the dark sidebar
                clot: '#2A0307',   // deepest red, sidebar shadow side
                brass: '#C49A5A',  // fine accents: active nav bar, "now" marker
            },
            fontFamily: {
                sans: ['"Instrument Sans"', 'system-ui', '-apple-system', '"Segoe UI"', 'Roboto', 'sans-serif'],
                display: ['Gloock', '"Times New Roman"', 'Georgia', 'serif'],
            },
        },
    },
    plugins: [],
};
