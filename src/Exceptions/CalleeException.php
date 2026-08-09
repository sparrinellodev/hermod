<?php

namespace Hermod\LaravelWamp\Exceptions;

/**
 * Exception thrown when an error occurs during RPC Callee operations.
 *
 * This includes failures related to procedure registration, unregistration,
 * or execution errors thrown inside the handler of a registered procedure.
 */
class CalleeException extends \RuntimeException
{
}