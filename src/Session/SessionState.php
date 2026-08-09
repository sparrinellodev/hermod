<?php

namespace Hermod\LaravelWamp\Session;

/**
 * Enumeration representing the possible states of a WAMP session.
 *
 * Tracks the lifecycle stages of a WAMP client session from initial disconnection, 
 * through handshaking and active communication, down to closure.
 */
enum SessionState
{
    /** No active connection exists. */
    case Closed;

    /** HELLO message sent, awaiting WELCOME response from the router. */
    case Establishing;

    /** WELCOME received, the session is fully active and established. */
    case Established;

    /** GOODBYE message sent, awaiting confirmation or termination. */
    case Closing;
}