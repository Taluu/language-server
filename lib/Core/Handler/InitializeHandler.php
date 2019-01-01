<?php

namespace Phpactor\LanguageServer\Core\Handler;

use Generator;
use LanguageServerProtocol\InitializeParams;
use LanguageServerProtocol\InitializeResult;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\TextDocumentSyncKind;
use Phpactor\LanguageServer\Core\Dispatcher\Handler;
use Phpactor\LanguageServer\Core\Event\EventEmitter;
use Phpactor\LanguageServer\Core\Event\LanguageServerEvents;
use Phpactor\LanguageServer\Core\Session\Session;
use Phpactor\LanguageServer\Core\Session\SessionManager;
use RuntimeException;

class InitializeHandler implements Handler
{
    /**
     * @var EventEmitter
     */
    private $emitter;

    /**
     * @var SessionManager
     */
    private $manager;

    public function __construct(EventEmitter $emitter, SessionManager $manager)
    {
        $this->emitter = $emitter;
        $this->manager = $manager;
    }

    public function methods(): array
    {
        return [
            'initialize' => 'initialize',
            'initialized' => 'initialized',
        ];
    }

    public function initialize(
        array $capabilities = [],
        array $initializationOptions = [],
        ?int $processId = null,
        ?string $rootPath = null,
        ?string $rootUri = null,
        ?string $trace = null
    ): Generator {
        if (!$rootUri && $rootPath) {
            $rootUri = $rootPath;
        }

        if (!$rootUri) {
            throw new RuntimeException(
                'rootUri (or deprecated rootPath) must be specified'
            );
        }

        $this->manager->load(new Session($rootUri, $processId));
        $this->emitter->emit(LanguageServerEvents::INITIALIZED, [new InitializeParams(
            $capabilities,
            $initializationOptions,
            $processId,
            $rootPath,
            $rootUri,
            $trace
        )]);
        yield $this->gatherServerCapabilities($capabilities);
    }

    public function initialized()
    {
    }

    private function gatherServerCapabilities(array $capabilities): InitializeResult
    {
        $capabilities = new ServerCapabilities();
        $capabilities->textDocumentSync = TextDocumentSyncKind::FULL;
        
        $result = new InitializeResult();
        $result->capabilities = $capabilities;
        $this->emitter->emit(
            LanguageServerEvents::CAPABILITIES_REGISTER,
            [ $capabilities ]
        );
        return $result;
    }
}
