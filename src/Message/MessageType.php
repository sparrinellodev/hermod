<?php

namespace Hermod\LaravelWamp\Message;

/**
 * Enum representing WAMP protocol message type identifiers.
 *
 * Each integer value corresponds to a specific message type defined by the WAMP 
 * (Web Application Messaging Protocol) specification, categorized across session 
 * lifecycle management, Pub/Sub, RPC roles, and authentication.
 */
enum MessageType: int
{
    // Session
    case HELLO = 1;
    case WELCOME = 2;
    case ABORT = 3;
    case GOODBYE = 6;

    // Error (cross-cutting across all roles/types)
    case ERROR = 8;

    // PubSub
    case PUBLISH = 16;
    case PUBLISHED = 17;
    case SUBSCRIBE = 32;
    case SUBSCRIBED = 33;
    case UNSUBSCRIBE = 34;
    case UNSUBSCRIBED = 35;
    case EVENT = 36;

    // RPC - Caller
    case CALL = 48;
    case RESULT = 50;

    // RPC - Callee
    case REGISTER = 64;
    case REGISTERED = 65;
    case UNREGISTER = 66;
    case UNREGISTERED = 67;
    case INVOCATION = 68;
    case YIELD = 70;

    // Authentication
    case CHALLENGE = 4;
    case AUTHENTICATE = 5;
}