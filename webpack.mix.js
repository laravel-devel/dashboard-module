const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

mix.setPublicPath('../../public').mergeManifest();

mix.sass(__dirname + '/Resources/sass/dashboard.scss', 'css/devel/dashboard.css')
    .copy(__dirname + '/Resources/sass/dashboard/line-awesome.min.css', '../../public/css/devel/line-awesome.min.css')
    .copyDirectory(__dirname + '/Resources/fonts', '../../public/css/fonts');

// Compile the main dashboard js files and all the dashboard extension files
// from all the modules
let modules = Object.keys(require('../modules.json'));

let compiled = [
    'js/devel/dashboard/develdashboard.js',
];

mix.js(__dirname + '/Resources/js/dashboard.js', 'js/devel/dashboard/develdashboard.js')

for (let name of modules) {
    if (name === 'DevelDashboard') {
        continue;
    }

    let source = __dirname + '/../' + name + '/Resources/js/dashboard.js';
    let path = 'js/devel/dashboard/' + name.toLowerCase() + '.js';

    if (File.exists(source)) {
        mix.js(source, path);

        compiled.push(path);
    }
}

compiled.push('js/devel/dashboard/develdashboard-after.js');

mix.js(__dirname + '/Resources/js/dashboard-after.js', 'js/devel/dashboard/develdashboard-after.js')

// Now combine all the files
compiled = compiled.map(path => {
    return __dirname + '/../../public/' + path;
});

mix.scripts(
    compiled, __dirname + '/../../public/js/devel/dashboard.js'
);

if (mix.inProduction()) {
    mix.version();
}