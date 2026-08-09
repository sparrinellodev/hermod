<?php

namespace Hermod\LaravelWamp\Exceptions;

/**
 * Exception thrown when an error occurs during Pub/Sub operations.
 *
 * This includes failures related to publishing events, managing topic 
 * subscriptions, or processing incoming publication notifications.
 */
class PubSubException extends \RuntimeException
{
}