<?php
/**
 * Dependency injection container.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;

defined( 'ABSPATH' ) || exit;

/**
 * A lightweight DI container with constructor auto-wiring.
 *
 * Classes are resolved recursively through reflection: any constructor
 * parameter with a class type-hint is resolved from the container, so
 * services declare their dependencies instead of reaching for globals
 * or singletons. Unbound classes resolve as shared instances.
 */
final class Container {

	/**
	 * Registered bindings, keyed by identifier.
	 *
	 * @var array<string, array{concrete: Closure|string, shared: bool}>
	 */
	private array $bindings = array();

	/**
	 * Resolved shared instances, keyed by identifier.
	 *
	 * @var array<string, object>
	 */
	private array $instances = array();

	/**
	 * Register a binding.
	 *
	 * @param string              $id       Identifier, usually a class or interface name.
	 * @param Closure|string|null $concrete Factory closure, concrete class name, or null to use $id.
	 * @param bool                $shared   Whether to reuse a single instance.
	 */
	public function bind( string $id, Closure|string|null $concrete = null, bool $shared = false ): void {
		$this->bindings[ $id ] = array(
			'concrete' => $concrete ?? $id,
			'shared'   => $shared,
		);

		unset( $this->instances[ $id ] );
	}

	/**
	 * Register a shared (single-instance) binding.
	 *
	 * @param string              $id       Identifier, usually a class or interface name.
	 * @param Closure|string|null $concrete Factory closure, concrete class name, or null to use $id.
	 */
	public function singleton( string $id, Closure|string|null $concrete = null ): void {
		$this->bind( $id, $concrete, true );
	}

	/**
	 * Register an existing object as a shared instance.
	 *
	 * @param string $id       Identifier, usually a class or interface name.
	 * @param object $instance The instance to store.
	 */
	public function instance( string $id, object $instance ): void {
		$this->instances[ $id ] = $instance;
	}

	/**
	 * Whether the container can resolve the given identifier.
	 *
	 * @param string $id Identifier to check.
	 */
	public function has( string $id ): bool {
		return isset( $this->instances[ $id ] ) || isset( $this->bindings[ $id ] ) || class_exists( $id );
	}

	/**
	 * Resolve an identifier to an object.
	 *
	 * @param string $id Identifier, usually a class or interface name.
	 *
	 * @throws ContainerException When the identifier cannot be resolved.
	 */
	public function get( string $id ): object {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		$binding = $this->bindings[ $id ] ?? array(
			'concrete' => $id,
			'shared'   => true,
		);

		$concrete = $binding['concrete'];

		$object = $concrete instanceof Closure
			? $concrete( $this )
			: $this->build( $concrete );

		if ( $binding['shared'] ) {
			$this->instances[ $id ] = $object;
		}

		return $object;
	}

	/**
	 * Build a class through reflection, resolving constructor dependencies.
	 *
	 * @param string $class_name Concrete class name.
	 *
	 * @throws ContainerException When the class or a dependency cannot be resolved.
	 */
	private function build( string $class_name ): object {
		if ( ! class_exists( $class_name ) ) {
			throw new ContainerException(
				sprintf( 'Class "%s" does not exist and cannot be resolved.', esc_html( $class_name ) )
			);
		}

		$reflection = new ReflectionClass( $class_name );

		if ( ! $reflection->isInstantiable() ) {
			throw new ContainerException(
				sprintf( 'Class "%s" is not instantiable; bind a concrete implementation.', esc_html( $class_name ) )
			);
		}

		$constructor = $reflection->getConstructor();

		if ( null === $constructor ) {
			return new $class_name();
		}

		$arguments = array();

		foreach ( $constructor->getParameters() as $parameter ) {
			$type = $parameter->getType();

			if ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
				$arguments[] = $this->get( $type->getName() );
				continue;
			}

			if ( $parameter->isDefaultValueAvailable() ) {
				$arguments[] = $parameter->getDefaultValue();
				continue;
			}

			throw new ContainerException(
				sprintf(
					'Unresolvable constructor parameter "$%s" for class "%s".',
					esc_html( $parameter->getName() ),
					esc_html( $class_name )
				)
			);
		}

		return $reflection->newInstanceArgs( $arguments );
	}
}
