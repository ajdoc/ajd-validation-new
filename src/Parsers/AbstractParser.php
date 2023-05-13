<?php 

namespace AjdVal\Parsers;

use AjDic\AjDic;
use InvalidArgumentException;

abstract class AbstractParser implements ParserInterface
{
	protected AjDic|null $container;
    protected object|null $validator = null;

    public function setContainer(AjDic $container): void
    {
        $this->container = $container;
    }

    public function getContainer(): AjDic|null
    {
        return $this->container;
    }

    public function setValditor($validator): void
    {
        $this->validator = $validator;
    }

    public function getValidator(): object|null
    {
        return $this->validator;   
    }

}