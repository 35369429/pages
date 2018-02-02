Web({
	title:'hello',
	load:function(){
		console.log( 'helo' );
	},
	onError:function( error ) {
		console.log( 'Error=', error, SERVICE_URL );
	}
});