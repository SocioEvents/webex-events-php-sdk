<?php

namespace  WebexEvents;

class Logger implements LoggerInterface
{
    public function log($level, $message) {
        // dev>null
    }

    public function info($message) {
        $this->log('info', $message);
    }

    public function error($message) {
        $this->log('error', $message);
    }

    public function debug($message) {
        $this->log('debug', $message);
    }
}