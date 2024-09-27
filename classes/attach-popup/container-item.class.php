<?php

namespace PPOM\Attach;

class ContainerItem {

	/**
	 * The ID of the component.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * The class that render the component HTML.
	 *
	 * @var ContainerView
	 */
	public $renderer;

	public function __construct( $id, $renderer ) {
		$this->id = $id;
		$this->renderer = $renderer;
	}

	/**
	 * Get the used renderer.
	 *
	 * @return ContainerView
	 */
	public function get_renderer() {
		return $this->renderer;
	}

	/**
	 * Set the render.
	 *
	 * @param ContainerView $renderer
	 *
	 * @return bool If the operation was successful.
	 */
	public function set_renderer( $renderer ) {
		if ( ! method_exists( $renderer, 'render' ) ) {
			return false;
		}

		$this->renderer = $renderer;
		return true;
	}

	/**
	 * Gets the ID of the container item.
	 *
	 * @return string The ID of the container item.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Sets the ID of the container item.
	 *
	 * @param string $id The new ID of the container item.
	 * @return void
	 */
	public function set_id( string $id ): void {
		$this->id = $id;
	}
}
