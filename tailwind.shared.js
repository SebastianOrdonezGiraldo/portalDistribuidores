import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export const theme = {
    extend: {
        colors: {
            brand: {
                primary: '#36B1BB',
                dark: '#0f6268',
                hover: '#2f9ca5',
                ink: '#050200',
                canvas: '#FFFFFF',
            },
            state: {
                pending: '#B45309',
                processing: '#0369A1',
                approved: '#166534',
                sent: '#1D4ED8',
                delivered: '#047857',
                canceled: '#B91C1C',
                error: '#991B1B',
            },
        },
        fontFamily: {
            sans: ['IBM Plex Sans', ...defaultTheme.fontFamily.sans],
            display: ['Sora', ...defaultTheme.fontFamily.sans],
        },
        borderRadius: {
            xl: '0.85rem',
            '2xl': '1.15rem',
            '3xl': '1.4rem',
        },
        boxShadow: {
            soft: '0 10px 30px -16px rgba(15, 23, 42, 0.30)',
            panel: '0 1px 2px rgba(15, 23, 42, 0.08), 0 18px 36px -24px rgba(15, 23, 42, 0.28)',
        },
    },
};

export const plugins = [forms];
