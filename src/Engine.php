<?php
/*
 * PSX is an open source PHP framework to develop RESTful APIs.
 * For the current version and information visit <https://phpsx.org>
 *
 * Copyright (c) Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PSX\Engine\FrankenPHP;

use PSX\Engine\DispatchInterface;
use PSX\Engine\EngineInterface;
use PSX\Http\Server\RequestFactory;
use PSX\Http\Server\ResponseFactory;
use PSX\Http\Server\Sender;

/**
 * Uses the FrankenPHP
 *
 * @see     https://github.com/swoole/swoole-src
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Engine implements EngineInterface
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function serve(DispatchInterface $dispatch): void
    {
        ignore_user_abort(true);

        $handler = static function () use ($dispatch) {
            $requestFactory = new RequestFactory($this->baseUrl, $_SERVER);
            $responseFactory = new ResponseFactory($_SERVER);
            $sender = new Sender();

            $response = $dispatch->route($requestFactory->createRequest(), $responseFactory->createResponse());

            $sender->send($response);
        };

        $maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);
        for ($nbRequests = 0; !$maxRequests || $nbRequests < $maxRequests; ++$nbRequests) {
            $keepRunning = \frankenphp_handle_request($handler);

            gc_collect_cycles();

            if (!$keepRunning) break;
        }
    }
}
