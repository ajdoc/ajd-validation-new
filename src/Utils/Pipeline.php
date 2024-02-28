<?php

namespace AjdVal\Utils;

use AjDic\AjDic;
use Closure;
use Throwable;

class Pipeline
{

    protected AjDic $containter;

	/**
     * The values being passed through the pipeline.
     *
     * @var mixed
     */
    protected array|mixed $passable;

    /**
     * The array of pipes.
     *
     * @var array
     */
    protected array $pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected string $method = 'handle';

    public function __construct() 
    {
        $this->container = new AjDic;
    }

    /**
     * Set the values being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send(array|mixed $passable): static
    {
        $this->passable = $passable;

        return $this;
    }

     /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through(array|mixed $pipes): static
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        $this->parsePipeAssoc($this->pipes);

        return $this;
    }

    /**
     * Push additional pipes onto the pipeline.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function pipe(array|mixed $pipes): static
    {
        array_push($this->pipes, ...(is_array($pipes) ? $pipes : func_get_args()));

        $this->parsePipeAssoc($this->pipes);

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get the method to call on the pipes.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination): mixed
    {
    	try {

    		$pipeline = array_reduce(
	            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
	        );
            
	        return $pipeline($this->passable);
    	} catch(Throwable $e) {
    		throw $e;
    	}
    }

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function result(): mixed
    {
    	try {
    		return $this->then(function ($passable) {
	            return $passable;
	        });
    	} catch(Throwable $e) {
    		throw $e;
    	}
    }

     /**
     * Get the final piece of the Closure.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the application.
     *
     * @return \Closure
     */
    protected function carry(): Closure
    {
    	$pipes = $this->pipes();
    	$lastItem = end($pipes);
    	
        return function ($stack, $pipe) use($lastItem) {
        	
            return function ($passable) use ($stack, $pipe, $lastItem) {
                try {

                    if (is_callable($pipe)) {
                        return $this->container->call($pipe, [$passable, $stack]);
                    } elseif (! is_object($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        $pipe = $this->container->make($name);

                        $parameters = array_merge([$passable, $stack], $parameters);
                    } else {
                        
                        $parameters = [$passable, $stack];
                    }

                    $carry = $this->executePipe($pipe, $parameters);

                    return $this->handleCarry($carry);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    /**
     * Execute pipe
     *
     * @param  mixed  $pipe
     * @param  array  $parameters
     * @return mixed
     */
    protected function executePipe(array|mixed $pipe, array $parameters): mixed
    {
    	return method_exists($pipe, $this->method)
    				? $this->container->call([$pipe, $this->method], $parameters)
    				: $this->container->call($pipe, $parameters);
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string  $pipe
     * @return array
     */
    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }


    /**
     * Parse associative pipe to string.
     *
     * @param  array|mixed  $pipes
     * @return void
     */
    public function parsePipeAssoc(array|mixed $pipes): void
    {
        if (empty($pipes)) {
        	return;
        }

        $parsedPipes = [];

        foreach ($pipes as $pipeKey => $pipe) {
        	if (is_numeric($pipeKey)) {
        		$parsedPipes[] = $pipe;
        		continue;
        	}

        	$pipe = is_array($pipe) ? $pipe : [$pipe];
        	
        	$parsedPipes[] = $pipeKey.':'.implode(',', $pipe);
        }

        $this->pipes = $parsedPipes;
    }

    /**
     * Get the array of configured pipes.
     *
     * @return array
     */
    protected function pipes(): array
    {
        return $this->pipes;
    }

    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handleCarry(mixed $carry): mixed
    {
        return $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param  \Throwable  $e
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function handleException(array|mixed $passable, Throwable $e): void
    {
        throw $e;
    }
}