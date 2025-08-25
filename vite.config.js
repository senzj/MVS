import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
    // server: {
    //     host: '0.0.0.0',
    //     port: 3000,
    //     strictPort: true,
    //     hmr: {
    //         host: '192.168.254.1', // Replace with your local IP
    //         port: 3000,
    //     },
    //     cors: {
    //         origin: ['http://192.168.254.1:8000'], // replace with your local IP and port
    //         credentials: true,
    //     }
    // },

    
});