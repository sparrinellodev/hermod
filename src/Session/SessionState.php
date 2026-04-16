<?php

namespace Hermod\Session;

enum SessionState
{
    case Closed;        // nessuna connessione
    case Establishing;  // HELLO inviato, in attesa di WELCOME
    case Established;   // WELCOME ricevuto, sessione attiva
    case Closing;       // GOODBYE inviato, in attesa di conferma
}
