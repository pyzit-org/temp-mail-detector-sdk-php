<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Base exception for all Pyzit SDK errors.
 *
 * Catch this class to handle any SDK error in one block:
 *
 *   try {
 *       $result = $client->check('user@example.com');
 *   } catch (PyzitException $e) {
 *       // handles all SDK errors
 *   }
 */
class PyzitException extends \RuntimeException
{
}