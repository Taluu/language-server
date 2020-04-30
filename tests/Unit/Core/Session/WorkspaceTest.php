<?php

namespace Phpactor\LanguageServer\Tests\Unit\Core\Session;

use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use LanguageServerProtocol\VersionedTextDocumentIdentifier;
use Phpactor\TestUtils\PHPUnit\TestCase;
use Phpactor\LanguageServer\Core\Session\Exception\UnknownDocument;
use Phpactor\LanguageServer\Core\Session\Workspace;

class WorkspaceTest extends TestCase
{
    /**
     * @var Workspace
     */
    private $workspace;

    protected function setUp(): void
    {
        $this->workspace = new Workspace();
    }

    public function testThrowsExceptionGetUnknown()
    {
        $this->expectException(UnknownDocument::class);
        $this->workspace->get('foobar');
    }

    public function testOpensDocument()
    {
        $expectedDocument = new TextDocumentItem();
        $expectedDocument->uri = 'foobar';
        $this->workspace->open($expectedDocument);
        $document = $this->workspace->get('foobar');

        $this->assertSame($expectedDocument, $document);
    }

    public function testThrowsExceptionUpdateUnknown()
    {
        $this->expectException(UnknownDocument::class);

        $expectedDocument = new VersionedTextDocumentIdentifier();
        $expectedDocument->uri = 'foobar';
        $this->workspace->update($expectedDocument, 'foobar');
    }

    public function testUpdatesDocument()
    {
        $originalDocument = new TextDocumentItem();
        $originalDocument->uri = 'foobar';
        $expectedDocument = new VersionedTextDocumentIdentifier();
        $expectedDocument->uri = $originalDocument->uri;
        $this->workspace->open($originalDocument);
        $this->workspace->update($expectedDocument, 'my new text');
        $document = $this->workspace->get('foobar');

        $this->assertEquals($expectedDocument->uri, $document->uri);
        $this->assertEquals('my new text', $document->text);
    }

    public function testDoesNotUpdateDocumentWithLowerVersionThanExistingDocument()
    {
        $originalDocument = new TextDocumentItem();
        $originalDocument->version = 5;
        $originalDocument->uri = 'foobar';
        $originalDocument->text = 'original document';
        $oldDocument = new VersionedTextDocumentIdentifier();
        $oldDocument->version = 4;
        $oldDocument->uri = $originalDocument->uri;

        $this->workspace->open($originalDocument);
        $this->workspace->update($oldDocument, 'my new text');

        $document = $this->workspace->get('foobar');

        $this->assertEquals($oldDocument->uri, $document->uri);
        $this->assertEquals('my original document', $document->text);
    }


    public function testReturnsNumberOfOpenFiles()
    {
        $originalDocument = new TextDocumentItem();
        $originalDocument->uri = 'foobar';
        $this->workspace->open($originalDocument);
        $this->assertEquals(1, $this->workspace->openFiles());
        $this->assertCount(1, $this->workspace);
    }

    public function testRemoveDocument()
    {
        $originalDocument = new TextDocumentItem();
        $originalDocument->uri = 'foobar';

        $this->workspace->open($originalDocument);
        $this->assertCount(1, $this->workspace);

        $identifier = new TextDocumentIdentifier('foobar');
        $this->workspace->remove($identifier);

        $this->assertCount(0, $this->workspace);
    }

    public function testIteratesOverDocuments()
    {
        $doc1 = new TextDocumentItem();
        $doc1->uri = 'foobar1';
        $doc2 = new TextDocumentItem();
        $doc2->uri = 'foobar2';

        $this->workspace->open($doc1);
        $this->workspace->open($doc2);

        $this->assertCount(2, iterator_to_array($this->workspace));
    }
}
