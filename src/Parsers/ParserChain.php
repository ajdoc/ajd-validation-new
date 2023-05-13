<?php 

namespace AjdVal\Parsers;

use InvalidArgumentException;
use AjdVal\Parsers\Metadata\ClassMetadata;

class ParserChain extends AbstractParser
{
	protected array $parsers;

	public function __construct(array $parsers)
    {
        foreach ($parsers as $parser) {
            if (!$parser instanceof ParserInterface) {
                throw new InvalidArgumentException(sprintf('Class "%s" is expected to implement ParserInterface.', get_class($parser)));
            }
        }

        $this->parsers = $parsers;
    }

    public function loadMetadata(ClassMetadata $class): array
    {
        $success = false;
        $container = $this->getContainer();
        $validator = $this->getValidator();

        $data = [];
            
        foreach ($this->parsers as $parser) {

        	$parser->setContainer($container);

            if (! empty($validator)) {
                $parser->setValidator($validator);
            }

            $data = array_merge($data, $parser->loadMetadata($class));
        }

        return $data;
    }

    public function getParsers(): array
    {
        return $this->parsers;
    }
}