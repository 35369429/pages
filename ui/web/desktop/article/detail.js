
let web = getWeb();
Page({
	data:{},
	curr_ad:0,
	onReady: function( param ) {
		this.advChange();
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
})