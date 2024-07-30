<?php

namespace WebexEvents;

interface HttpClientInterface
{
    function post(string $url, array $data, array $requestHeaders): array;
}