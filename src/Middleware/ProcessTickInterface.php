<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use WebSocket\Connection;

/**
 * WebSocket\Middleware\ProcessTickInterface interface.
 * Interface for incoming middleware implementations.
 */
interface ProcessTickInterface extends MiddlewareInterface
{
    public function processTick(ProcessTickStack $stack, Connection $connection): void;
}
