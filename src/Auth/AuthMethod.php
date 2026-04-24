<?php

namespace Hermod\Auth;

enum AuthMethod: string
{
    case Anonymous = 'anonymous';
    case Ticket = 'ticket';
    case WampCra = 'wampcra';
}
