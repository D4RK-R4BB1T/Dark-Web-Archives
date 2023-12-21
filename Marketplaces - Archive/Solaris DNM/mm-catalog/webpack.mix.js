const mix = require('laravel-mix');

// Disable mix-manifest.json
Mix.manifest.refresh = _ => void 0;
/*
mix.js('resources/assets/js/app.js', 'public/js')
   .sass('resources/assets/sass/app.scss', 'public/css');
*/

mix.sass('resources/assets/sass/app.scss', 'public/assets/css/theme.css');
