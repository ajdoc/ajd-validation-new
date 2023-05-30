<?php 

namespace AjdVal\Traits;

use AjDic\AjDic;
use AjdVal\ValidatorDto;
use AjdVal\Utils\Utils;

trait RuleCreatorTrait
{
	public static function createRule(...$arguments)
    {
    	$qualifiedClass = static::class;
    	$container = new AjDic;

    	if (isset($arguments[0]) && $arguments[0] instanceof ValidatorDto) {
    		$validatorDto = $arguments[0];

    		unset($arguments[0]);

    		$arguments = array_values($arguments);

    		if (iset($arguments[1]) && is_string($arguments[1])) {
    			$qualifiedClass = $arguments[1];
    		
	    		unset($arguments[1]);

	    		$arguments = array_values($arguments);
    		}

    	} else if(isset($arguments[0]) && is_string($arguments[0])) {

    		$qualifiedClass = $arguments[0];
    		
    		unset($arguments[0]);

    		$arguments = array_values($arguments);

    		$validatorDto = new ValidatorDto;
    	} else {
    		$validatorDto = new ValidatorDto;
    	}

    	$validatorBuilder = $validatorDto->getValidatorBuilder();
    	$rule = Utils::getShortNameClass($qualifiedClass);
    	
    	$handlers = ValidatorsTrait::resolveHandler($validatorBuilder, $rule, $qualifiedClass, $container, $arguments);

    	$arguments = $qualifiedClass::processHandlerPreInit($arguments, $qualifiedClass, $handlers);
    	
    	$ruleInstance = $container->makeWith($qualifiedClass, $arguments);

    	ValidatorsTrait::initRuleHandlers($handlers, $ruleInstance, $arguments);
   			
   		ValidatorsTrait::createNewExpressionRule($rule, $ruleInstance, $container, $arguments);

   		$validatorClass = $validatorBuilder->getValidatorClass();

   		$ruleInstance->setValidatorDto($validatorDto);
   		$ruleInstance->setValidator($validatorClass::create());

   		return $ruleInstance;
    }
}