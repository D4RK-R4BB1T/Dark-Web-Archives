const mix = require('laravel-mix');

mix.options({
        // Помещать изображения и шрифты в public/assets
        fileLoaderDirs: { images: 'assets/img', fonts: 'assets/fonts' }
    })
    .sass('resources/assets/sass/app.scss', 'public/assets/css/theme.css')
    .sass('resources/assets/sass/quests_map.scss', 'public/assets/css')
    .js('resources/assets/js/quests_map.js', 'public/assets/js')
    .version();