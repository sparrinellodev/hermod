<?php

namespace Hermod\LaravelWamp\Exceptions;

/**
 * Base exception thrown for generic WAMP client errors.
 *
 * This serves as a catch-all or parent exception for various client-side 
 * failures that do not fall under more specific sub-categories.
 */
class WampClientException extends \RuntimeException
{
}