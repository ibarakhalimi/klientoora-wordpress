<?php
/**
 * Hook loader.
 *
 * @package Klientoora_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and registers WordPress hooks.
 */
class Klientoora_Card_Loader {

	/**
	 * Registered actions.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $actions;

	/**
	 * Registered filters.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $filters;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Adds an action to the collection.
	 *
	 * @param string $hook          WordPress hook name.
	 * @param object $component     Object containing the callback.
	 * @param string $callback      Callback method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 *
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Adds a filter to the collection.
	 *
	 * @param string $hook          WordPress hook name.
	 * @param object $component     Object containing the callback.
	 * @param string $callback      Callback method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 *
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Adds a hook definition to a collection.
	 *
	 * @param array<int, array<string, mixed>> $hooks         Existing hook definitions.
	 * @param string                           $hook          WordPress hook name.
	 * @param object                           $component     Object containing the callback.
	 * @param string                           $callback      Callback method name.
	 * @param int                              $priority      Hook priority.
	 * @param int                              $accepted_args Number of accepted arguments.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Registers all actions and filters with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
