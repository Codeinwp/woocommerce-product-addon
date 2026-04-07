<?php

namespace PPOM\Attach;

/**
 * Base attach-popup view with an optional element id.
 */
class ContainerView {

	/**
	 * HTML element id for the rendered container.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Render the component.
	 *
	 * @return string
	 */
	public function render() {
		return '';
	}

	/**
	 * Gets the ID of the container view.
	 *
	 * @return string The ID of the container view.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Sets the ID of the container view.
	 *
	 * @param string $id The new ID of the container view.
	 *
	 * @return $this
	 */
	public function set_id( string $id ) {
		$this->id = $id;

		return $this;
	}
}
