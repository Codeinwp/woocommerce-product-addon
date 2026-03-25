/* Image loader */
function addListener( element, type, expression, bubbling ) {
	bubbling = bubbling || false;
	if ( window.addEventListener ) {
		// Standard
		element.addEventListener( type, expression, bubbling );
		return true;
	} else if ( window.attachEvent ) {
		// IE
		element.attachEvent( 'on' + type, expression );
		return true;
	}
	return false;
}

const ImageLoader = function ( url ) {
	this.url = url;
	this.image = null;
	this.loadEvent = null;
};

ImageLoader.prototype = {
	load() {
		this.image = document.createElement( 'img' );
		const url = this.url;
		const image = this.image;
		const loadEvent = this.loadEvent;
		addListener(
			this.image,
			'load',
			function ( e ) {
				if ( loadEvent != null ) {
					loadEvent( url, image );
				}
			},
			false
		);
		this.image.src = this.url;
	},
	getImage() {
		return this.image;
	},
};
/* End of image loader */
