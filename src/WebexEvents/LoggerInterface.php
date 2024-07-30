<?php

namespace WebexEvents;

interface LoggerInterface
{
    public function log($level, $message);

    public function info($message);

    public function error($message);

    public function debug($message);
}