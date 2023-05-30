<?php 

namespace AjdVal;

use AjdVal\Builder;
use AjdVal\Parsers\ParserInterface;
use AjdVal\Parsers\ParserChain;
use AjdVal\Parsers\Factory\MetadataFactoryInterface;
use AjdVal\Parsers\Factory\MetadataFactory;
use AjdVal\Validators\ValidatorsInterface;
use AjDic\AjDic;

class ValidatorDto
{
	public function __construct(
		protected Builder\ValidatorBuilderInterface|null $validatorBuilder = null
	)
	{
		$validatorBuilder = $validatorBuilder ?? new Builder\DefaultValidatorBuilder;
		$this->validatorBuilder = $validatorBuilder->initialize();
	}

	public function addValidatorBuilder(Builder\ValidatorBuilderInterface $validatorBuilder)
	{
		$clone = $this;
		
		$newBuilder = new Builder\CompositeValidatorBuilder($clone->validatorBuilder, $validatorBuilder->initialize());
		$clone->validatorBuilder = $newBuilder->initialize();
		
		return $clone;
	}

	public function getValidatorBuilder(): Builder\ValidatorBuilderInterface
	{
		return $this->validatorBuilder;
	}

	public function withValidatorBuilder(Builder\ValidatorBuilderInterface $validatorBuilder): self
    {
        $clone = clone $this;
        $clone->validatorBuilder = $validatorBuilder->initialize();

        return $clone;
    }

    public function getParser(ValidatorsInterface|null $validator): ParserInterface|null
    {
    	 $parsers = $this->validatorBuilder->getParsers();
    	 
    	 if (empty($parsers)) {
    	 	return null;
    	 }

    	 $parser = null;

		if (\count($parsers) > 1) {
			$parser = new ParserChain($parsers);
		} elseif (1 === \count($parsers)) {
			$parser = $parsers[0];
		}
		
		$parser->setContainer(new AjDic);

		if (null !== $validator) {
			$parser->setValidator($validator);
		}

		return $parser;
    }

    public function getMetadataFactory(ValidatorsInterface|null $validator): MetadataFactoryInterface
    {
    	return new MetadataFactory($this->getParser($validator));
    }
}