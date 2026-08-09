<?php

namespace Hermod\LaravelWamp\Exceptions;

/**
 * Exception thrown when a reconnection error occurs.
 *
 * This includes failures during the automated retry process, when 
 * the maximum number of reconnection attempts has been exhausted, 
 * or when a reconnection strategy encounters an unrecoverable state.
 */
class ReconnectException extends \RuntimeException
{
}