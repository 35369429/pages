let web = getWeb();
Page({
	data:{},
	
	onReady: function( get ) {

		var that=  this;

		console.log(that.data);


		console.log( 'page onReady data=', this.data ,  ' web.title=', web.title );
	}
})