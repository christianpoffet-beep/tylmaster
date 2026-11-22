<?php

return [

    /*
     * Usage up to and including this day was settled by hand before the tool
     * took over (the statements up to the end of 2022). Imported lines from
     * before it are marked as settled and never go into a statement.
     */
    'settled_until' => '2022-12-31',

    /*
     * Balances from this amount (CHF) on are suggested for payout; smaller
     * ones are carried forward (they can still be paid out by hand).
     */
    'payout_minimum' => 100,

];
