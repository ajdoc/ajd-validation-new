<?php 

namespace AjdVal;

use AjdVal\Validators\ValidatorsInterface;
use AjdVal\Expression\ExpressionBuilderValidator;
use AjdVal\Builder;
use InvalidArgumentException;

class Validator 
{
	public function __construct(protected readonly ValidatorDto $validatorDto) 
    {

    }

    public function getValidatorDto(): ValidatorDto
    {
        return $this->validatorDto;
    }

    public static function getValidator(ValidatorDto|null $validatorDto = null): string
    {
        $valDto = ! empty($validatorDto) ? $validatorDto : new ValidatorDto;

        $self = new static($valDto);
        
        return $self->getValidatorBuilder()->getValidatorClass();
    }

	public static function create(
        array $validatorBuilders = [], 
        ValidatorDto|null $validatorDto = null
    ): ValidatorsInterface|ExpressionBuilderValidator {
        
        $valDto = ! empty($validatorDto) ? $validatorDto : new ValidatorDto;

        $self = new static($valDto);

        $validator = $self->getValidatorDto()->getValidatorBuilder()->getValidatorClass();

        if (! empty($validatorBuilders)) {
            foreach ($validatorBuilders as $validatorBuilder) {

                if (! $validatorBuilder instanceof Builder\ValidatorBuilderInterface) {
                    throw new InvalidArgumentException('Invalid Validator Builder.');
                }

                $validator::addValidatorBuilder($validatorBuilder);
            }
        } else {
            $validator::withValidatorDto($self->getValidatorDto());
        }

        return $validator::create();
    }
}