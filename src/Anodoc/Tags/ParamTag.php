<?php

namespace Tiny\Anodoc\Tags;

class ParamTag extends Tag
{

    private $tag_name = '';
    private $value = [];

    public function __construct($tag_name, $value)
    {
        $ret = preg_match('/(\w+)\s+\$(\w+)\s+(.+)/ms', $value, $matches);
        if($ret){
            $this->value = [
                'type' => trim($matches[1]),
                'name' => trim($matches[2]),
                'description' => trim($matches[3])
            ];
        } else {
            preg_match('/(\w+)\s+\$(\w+)\s*/ms', $value, $matches);
            $this->value = [
                'type' => trim($matches[1]),
                'name' => trim($matches[2]),
                'description' => ''
            ];
        }

        $this->tag_name = $tag_name;
    }

    public function getKey(){
        return $this->value['name'];
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getTagName()
    {
        return $this->tag_name;
    }

    public function __toString()
    {
        return (string)$this->value;
    }

}