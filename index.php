<?php

use App\Support\WebRoot;

/*
 * Fallback front controller for hosts WITHOUT mod_rewrite, when the whole
 * project was uploaded into the web root or a subfolder. With mod_rewrite the
 * root .htaccess sends every request into public/ and this file never runs.
 *
 * Pages are then reached as /index.php/login (Laravel builds those links
 * itself) and CSS/JS/images are loaded straight from public/.
 * Pointing the domain at public/ is still the recommended setup.
 */

require __DIR__.'/vendor/autoload.php';

[$_SERVER, $assetUrl] = WebRoot::withoutRewrite($_SERVER);

// Set before .env is loaded, so it wins unless the server already sets ASSET_URL.
if (getenv('ASSET_URL') === false && ! isset($_ENV['ASSET_URL']) && ! isset($_SERVER['ASSET_URL'])) {
    putenv('ASSET_URL='.$assetUrl);
    $_ENV['ASSET_URL'] = $_SERVER['ASSET_URL'] = $assetUrl;
}

require __DIR__.'/public/index.php';
