<?php 

namespace AjdVal\Exceptions;

use AjdVal\Contracts\ExceptionInterface;
use AjdVal\Traits\ErrorsTrait;
use Exception;

class ValidationExceptions extends Exception implements ExceptionInterface 
{
	use ErrorsTrait;

	protected $params = [];

 	const ERR_DEFAULT = 1;
    const ERR_NEGATIVE = 2;
    const STANDARD = 0;

    public static $defaultMessages = [
        self::ERR_DEFAULT => [
            self::STANDARD => 'Data validation failed for :field',
        ],
        self::ERR_NEGATIVE => [
            self::STANDARD => 'Data validation failed for :field',
        ],
    ];

    protected $mode = self::ERR_DEFAULT;
    protected $id = 'validation';
    protected $name = '';


    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function guessId($idPass = null)
    {
        if (!empty($this->id) AND $this->id != 'validation') {
            return $this->id;
        }

        if (!empty($idPass)) {
            $className = $idPass;
        } else {
            $className = get_called_class();
        }

        $pieces = explode('\\', $className);
        $exceptionShortName = end($pieces);
        $ruleShortName = str_replace('Exception', '', $exceptionShortName);

        $ruleName = lcfirst($ruleShortName);
        
        return $ruleName;
    }

    public function getExceptionMessage()
    {

    	return $this->buildMessageErr();
    }

    public function setMode($mode)
    {
        $this->mode = $mode;

        if ($this->mode == self::ERR_NEGATIVE) {

            if (! $this->hasParam('inverse')) {
                $this->setParam('inverse', true);
            }
        }

        $this->buildMessageErr();

        return $this;
    }

    public function configure( array $params = array())
    {       
        $idPass = (isset($params['id_pass']) && !empty($params['id_pass'])) ? $params['id_pass'] : null;
        
        $guessId = $this->guessId($idPass);
        $this->setId($guessId);
    	$this->setParams( $params );        
        
    	// $this->localize();

    	if (isset($params['inverse']) && !empty($params['inverse'])) {
    		$this->setMode(self::ERR_NEGATIVE);
    	} else {
    		$this->setMode(self::ERR_DEFAULT);	
    	}
    }

    public function setParams(array $params)
    {
    	$this->params 	= $params;
         
    	return $this;
    }

    public function setParam($key, $value)
    {
        $this->params[$key] = $value;

        $this->buildMessageErr();

        return $this;
    }

    public function hasParam($name)
    {
        return isset($this->params[$name]);
    }

    public function getParam($name)
    {
        return $this->hasParam($name) ? $this->params[$name] : false;
    }

    public function getParams()
    {
        return $this->params;
    }

  	public function chooseMessage()
    {
        return key(static::$defaultMessages[$this->mode]);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public static function setDefaultMessages(array $defaultMessages)
    {
        static::$defaultMessages = $defaultMessages;
    }

    protected function buildMessageErr()
    {
        $messageKey = $this->chooseMessage();

        $message_str = static::$defaultMessages[$this->mode][$messageKey];
        
        $message = static::replaceErrorPlaceholder($this->getParams(), $message_str);
        
        $appendError = $this->getParam('appendError');
        
        if (empty($message)) {
        	$message = $message_str;
        }

        if (! empty($appendError)) {
            $message .= ' '.$appendError;
        }

        return $message;
    }
}