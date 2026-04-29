import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/homepage.css',
                'resources/js/homepage.js',
                'resources/css/signup.css',
                'resources/js/signup.js',
                'resources/css/user_login.css',
                'resources/js/user_login.js',
                'resources/css/upcoming_movies.css',
                'resources/css/setup_timetable.css',
                'resources/js/setup_timetable.js',
                'resources/css/movie_proposals.css',
                'resources/js/movie_proposals.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
