<?php

namespace Tiny;

use Tiny\Anodoc\ClassDoc;
use Tiny\Anodoc\ClassDoc\InvalidAttributeDoc;
use Tiny\Anodoc\ClassDoc\InvalidMethodDoc;
use Tiny\Anodoc\Collection\NotATagException;
use Tiny\Anodoc\Collection\NotATagGroupException;
use Tiny\Anodoc\Parser;
use Tiny\Anodoc\RawDocRetriever;

class AnodocBase
{
    /** @var Parser $_return */
    private $parser = null;

    function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    static function getNew()
    {
        return new self(new Parser);
    }

    /**
     * @param $class
     * @return ClassDoc
     * @throws NotATagException
     * @throws NotATagGroupException
     * @throws InvalidAttributeDoc
     * @throws InvalidMethodDoc
     * @throws \ReflectionException
     */
    function getDoc($class)
    {
        $retriever = new RawDocRetriever($class);
        return new ClassDoc(
            $class, $this->parser->parse($retriever->rawClassDoc()),
            $this->getParsedDocs($retriever->rawMethodDocs()),
            $this->getParsedDocs($retriever->rawAttrDocs())
        );
    }

    /**
     * @param $rawDocs
     * @return array
     * @throws NotATagException
     * @throws NotATagGroupException
     */
    private function getParsedDocs($rawDocs)
    {
        $docs = array();
        foreach ($rawDocs as $name => $doc) {
            $docs[$name] = $this->parser->parse($doc);
        }
        return $docs;
    }

    function registerTag($tag_name, $tag_class)
    {
        $this->parser->registerTag($tag_name, $tag_class);
    }


}