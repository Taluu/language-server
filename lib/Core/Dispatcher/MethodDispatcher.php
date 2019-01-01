<?php

namespace Phpactor\LanguageServer\Core\Dispatcher;

use Generator;
use Phpactor\LanguageServer\Core\Rpc\Message;
use Phpactor\LanguageServer\Core\Rpc\RequestMessage;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use RuntimeException;

class MethodDispatcher implements Dispatcher
{
    /**
     * @var ArgumentResolver
     */
    private $argumentResolver;

    /**
     * @var HandlerRegistry
     */
    private $handlerRegistry;

    public function __construct(ArgumentResolver $argumentResolver, HandlerRegistry $handlerRegistry)
    {
        $this->argumentResolver = $argumentResolver;
        $this->handlerRegistry = $handlerRegistry;
    }

    public function dispatch(RequestMessage $request): Generator
    {
        $handler = $this->handlerRegistry->get($request->method);

        $method = $this->resolveHandlerMethod($handler, $request);

        $arguments = $this->argumentResolver->resolveArguments(
            $handler,
            $method,
            $request->params
        );

        $messages = $handler->$method(...$arguments);

        if (null === $messages) {
            return;
        }

        if (!$messages instanceof Generator) {
            throw new RuntimeException(sprintf(
                '%s handler "%s" did not return a generator, it returned a: %s',
                $request->method,
                get_class($handler),
                is_object($messages) ? get_class($messages) : gettype($messages)
            ));
        }

        foreach ($messages as $message) {
            if ($message instanceof Message) {
                yield $message;
                continue;
            }
            yield new ResponseMessage($request->id, $message);
        }
    }

    private function resolveHandlerMethod(Handler $handler, RequestMessage $request): string
    {
        $handlerMethods = $handler->methods();
        $method = $handlerMethods[$request->method];

        if (!method_exists($handler, $method)) {
            throw new RuntimeException(sprintf(
                'Handler "%s" for method "%s" does not have the "%s" method defined, it has "%s"',
                get_class($handler),
                $request->method,
                $method,
                implode('", "', get_class_methods($handler))
            ));
        }

        return $method;
    }
}
