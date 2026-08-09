<?php

namespace Hermod\LaravelWamp\Exceptions;

use RuntimeException;

/**
 * Exception thrown when an error occurs during WAMP session management.
 *
 * This includes failures related to session establishment, unexpected 
 * session closures, or operations attempted on an inactive or unestablished session.
 */
class SessionException extends RuntimeException
{
}