<?php

namespace Hermod\LaravelWamp\Exceptions;

use RuntimeException;

/**
 * Exception thrown when message serialization or deserialization fails.
 *
 * This includes errors encountered when encoding data structures into raw 
 * payloads (e.g., JSON encoding errors) or decoding incoming streams.
 */
class SerializationException extends RuntimeException
{
}