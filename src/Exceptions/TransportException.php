<?php

namespace Hermod\LaravelWamp\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a transport-level network error occurs.
 *
 * This includes failures during socket connection establishment, network 
 * timeouts, unexpected socket disconnections, or read/write errors over the transport stream.
 */
class TransportException extends RuntimeException
{
}