<?php

return [
    /*
     * Your license server (the separate license-server/ app on your own
     * hosting). The installer sends only the purchase code + this site's
     * domain there; your Envato token never ships with the item.
     * Change the default before packaging if you host it elsewhere.
     */
    'server' => env('LICENSE_SERVER_URL', 'https://license.digitalstorebd.fun'),

    // Where the verified license is remembered on the buyer's install.
    'file' => storage_path('app/license.json'),
];
