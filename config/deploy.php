<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Finish a deploy on its own
    |--------------------------------------------------------------------------
    |
    | The hosting has no SSH. After Plesk has deployed new code, the first
    | request runs what the "Migrationen & Cache" button does - but only when
    | the deploy changed migrations, config or routes. On everywhere except
    | local and testing, so the server needs no extra setting while a local
    | or test database is never migrated behind anyone's back.
    |
    */

    'auto_finish' => (bool) env('DEPLOY_AUTO_FINISH', !in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)),

    // Where the last run is recorded: which state it finished, when, whether it worked.
    'marker' => storage_path('app/deploy-finished.json'),

    // Files younger than this may belong to a deploy that is still copying.
    'settle_seconds' => 30,

];
