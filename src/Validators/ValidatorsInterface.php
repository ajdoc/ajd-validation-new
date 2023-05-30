<?php 

namespace AjdVal\Validators;

interface ValidatorsInterface
{
	/**
     * Gets The Validator Instance.
     *
     * @return \AjdVal\Validators\ValidatorsInterface
     */
    public function getValidator(): ValidatorsInterface;
}