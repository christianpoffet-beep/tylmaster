<?php

namespace App\Services\Royalties;

/** A statement that cannot be finalized or undone; the message is shown to the user. */
class RoyaltyStatementException extends \RuntimeException
{
}
