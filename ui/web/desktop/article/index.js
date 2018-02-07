let web = getWeb();
Page({
	data:{},
	curr_ad:0,
	
	onReady: function( get ) {
		this.init();
	},

	init: function(){
		this.advChange();
	 	this.slide();
	},


	/**
	 * 轮播广告代码
	 * @return
	 */
	advChange: function() {

		let advs = this.data.advs || [];
		let idx = this.curr_ad;
		let adv =advs[idx];
		$('.alimama a img').attr('src', adv.image);
		$('.alimama a').attr('href', adv.link);

		// 下一个
		this.curr_ad++;
		if ( this.curr_ad >= advs.length ) {
			this.curr_ad = 0;
		}
		
		setTimeout(()=>{
			this.advChange();
		}, 2000);
	},

	/**
	 * 焦点图
	 * @return {[type]} [description]
	 */
	slide: function(){

		$('.slide .tags li').mouseover(function( event ){
			let id = $(this).attr('data-id');
			$('.slide .tags li').removeClass('active');
			$(this).addClass('active');

			$('.slide .items li').hide();
			$('.slide .items [data-id='+id+']').fadeIn();
		});
	}

})