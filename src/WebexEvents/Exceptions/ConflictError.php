<?php

namespace WebexEvents\Exceptions;

class ConflictError extends WebexEventsBaseRequestError
{
    protected bool $retryable = true;
}