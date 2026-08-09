<?php

namespace Hermod\LaravelWamp\Exceptions;

use RuntimeException;

/**
 * Exception thrown when an invalid or malformed WAMP message is encountered.
 *
 * This typically occurs when a received payload does not conform to the 
 * expected WAMP message format, contains unexpected data types, 
 * or lacks mandatory message fields.
 */
class InvalidMessageException extends RuntimeException
{
}