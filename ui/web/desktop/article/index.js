let web = getWeb();
Page({
	data:{},
	
	onReady: function( get ) {
		this.init();
	},

	init: function(){
		$("[data-toggle='popover']").popover();
	}
})