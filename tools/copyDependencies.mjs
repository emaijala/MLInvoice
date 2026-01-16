import { basename } from 'node:path';
import { copyFileSync  } from 'node:fs';

console.log('Copying dependencies...');

const js = [
    'node_modules/bootstrap/dist/js/bootstrap.min.js',
    'node_modules/bootstrap/dist/js/bootstrap.min.js.map',
    'node_modules/@popperjs/core/dist/umd/popper.min.js',
    'node_modules/@popperjs/core/dist/umd/popper.min.js.map',
    'node_modules/jquery/dist/jquery.min.js',
    'node_modules/datatables.net/js/jquery.dataTables.min.js',
    'node_modules/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'node_modules/datatables.net-responsive/js/dataTables.responsive.min.js',
    'node_modules/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
    'node_modules/datatables.net-buttons/js/dataTables.buttons.js',
    'node_modules/datatables.net-buttons/js/buttons.html5.min.js',
    'node_modules/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js',
    'node_modules/datatables.net-buttons/js/buttons.colVis.min.js',
    'node_modules/moment/min/moment-with-locales.min.js',
    'node_modules/moment/min/moment-with-locales.min.js.map',
    'node_modules/select2/dist/js/select2.js',
];

js.forEach((filename) => {
    copyFileSync(filename, 'assets/js/vendor/' + basename(filename));
});

// Select2 locales
const locales = ['fi', 'en', 'sv'];
locales.forEach((locale) => {
    copyFileSync('node_modules/select2/dist/js/i18n/' + locale + '.js', 'assets/js/vendor/select2-i18n-' + locale + '.js');
});

console.log('Done copying dependencies.');
