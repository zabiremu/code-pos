<?php

return [
    /*
     * Your license server (the separate license-server/ app on your own
     * hosting). The installer sends only the purchase code + this site's
     * domain there; your Envato token never ships with the item.
     * Change the default before packaging if you host it elsewhere.
     * `?:` rather than env()'s default so a blank `LICENSE_SERVER_URL=` line
     * falls back too - env() only uses its default when the key is absent.
     */
    'server' => env('LICENSE_SERVER_URL') ?: 'https://license.digitalstorebd.fun',

    // Where the verified license is remembered on the buyer's install.
    'file' => storage_path('app/license.json'),
];
