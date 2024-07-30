<?php

namespace WebexEvents\Exceptions;

class SecondBasedQuotaIsReachedError extends WebexEventsBaseRequestError
{
    protected bool $retryable = true;
}

