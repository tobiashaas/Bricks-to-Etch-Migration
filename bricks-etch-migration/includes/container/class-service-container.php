<?php
namespace Bricks2Etch\Container;

use Closure;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

class B2E_Service_Container implements ContainerInterface {

	/** @var array<string, mixed> */
	private $services = array();

	/** @var array<string, object> */
	private $resolved = array();

	/** @var array<string, bool> */
	private $factories = array();

	/** @var array<string, string|Closure> */
	private $bindings = array();

	/**
	 * Register a service definition.
	 *
	 * @param string $id
	 * @param mixed  $concrete
	 *
	 * @return $this
	 */
	public function set( $id, $concrete ) {
		return $this->singleton( $id, $concrete );
	}

	/**
	 * Register a singleton service.
	 *
	 * @param string $id
	 * @param mixed  $concrete
	 *
	 * @return $this
	 */
	public function singleton( $id, $concrete ) {
		$this->services[ $id ] = $concrete;
		unset( $this->factories[ $id ], $this->resolved[ $id ] );

		return $this;
	}

	/**
	 * Register a factory service.
	 *
	 * @param string $id
	 * @param mixed  $concrete
	 *
	 * @return $this
	 */
	public function factory( $id, $concrete ) {
		$this->services[ $id ]  = $concrete;
		$this->factories[ $id ] = true;
		unset( $this->resolved[ $id ] );

		return $this;
	}

	/**
	 * Bind an abstract service to a concrete implementation.
	 *
	 * @param string          $abstract
	 * @param string|Closure  $concrete
	 *
	 * @return $this
	 */
	public function bind( $abstract, $concrete ) {
		$this->bindings[ $abstract ] = $concrete;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get( $id ) {
		if ( isset( $this->resolved[ $id ] ) ) {
			return $this->resolved[ $id ];
		}

		if ( isset( $this->bindings[ $id ] ) ) {
			$this->services[ $id ] = $this->bindings[ $id ];
		}

		if ( ! isset( $this->services[ $id ] ) ) {
			if ( class_exists( $id ) ) {
				$this->services[ $id ] = $id;
			} else {
				throw new B2E_Service_Not_Found_Exception( sprintf( 'Service "%s" is not registered in the container.', $id ) );
			}
		}

		$concrete = $this->services[ $id ];
		$object   = $this->resolve( $concrete );

		if ( ! isset( $this->factories[ $id ] ) ) {
			$this->resolved[ $id ] = $object;
		}

		return $object;
	}

	/**
	 * {@inheritdoc}
	 */
	public function has( $id ) {
		return isset( $this->resolved[ $id ] ) || isset( $this->services[ $id ] ) || isset( $this->bindings[ $id ] );
	}

	/**
	 * Clear resolved instances.
	 *
	 * @return $this
	 */
	public function flush() {
		$this->resolved = array();

		return $this;
	}

	/**
	 * Resolve a service definition.
	 *
	 * @param mixed $concrete
	 *
	 * @return mixed
	 */
	private function resolve( $concrete ) {
		if ( $concrete instanceof Closure ) {
			return $concrete( $this );
		}

		if ( is_object( $concrete ) && ! $concrete instanceof Closure ) {
			return $concrete;
		}

		if ( ! is_string( $concrete ) ) {
			throw new B2E_Service_Container_Exception( 'Container cannot resolve the given service definition.' );
		}

		if ( isset( $this->bindings[ $concrete ] ) ) {
			return $this->resolve( $this->bindings[ $concrete ] );
		}

		if ( ! class_exists( $concrete ) ) {
			throw new B2E_Service_Container_Exception( sprintf( 'Class "%s" does not exist.', $concrete ) );
		}

		try {
			$reflection = new ReflectionClass( $concrete );
		} catch ( ReflectionException $exception ) {
			throw new B2E_Service_Container_Exception( $exception->getMessage(), 0, $exception );
		}

		if ( ! $reflection->isInstantiable() ) {
			throw new B2E_Service_Container_Exception( sprintf( 'Class "%s" is not instantiable.', $concrete ) );
		}

		$constructor = $reflection->getConstructor();

		if ( null === $constructor ) {
			return new $concrete();
		}

		$dependencies = array();

		foreach ( $constructor->getParameters() as $parameter ) {
			$type = $parameter->getType();

			if ( null === $type ) {
				if ( $parameter->isOptional() ) {
					$dependencies[] = $parameter->getDefaultValue();
					continue;
				}

				throw new B2E_Service_Container_Exception( sprintf( 'Unable to resolve dependency "%s" in class "%s".', $parameter->getName(), $concrete ) );
			}

			if ( $type->isBuiltin() ) {
				if ( $parameter->isOptional() ) {
					$dependencies[] = $parameter->getDefaultValue();
					continue;
				}

				throw new B2E_Service_Container_Exception( sprintf( 'Cannot resolve built-in dependency "%s" for class "%s".', $parameter->getName(), $concrete ) );
			}

			$dependencyClass = $type->getName();
			$dependencies[]  = $this->get( $dependencyClass );
		}

		return $reflection->newInstanceArgs( $dependencies );
	}
}

class B2E_Service_Not_Found_Exception extends Exception implements NotFoundExceptionInterface {

}

class B2E_Service_Container_Exception extends Exception implements ContainerExceptionInterface {

}
