<?php

namespace WebexEvents\Exceptions;

class TooManyRequestError extends WebexEventsBaseRequestError
{
    protected bool $retryable = true;
}