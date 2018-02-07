
let web = getWeb();
Page({
	data:{},
	curr_ad:0,
	maxW:0,
	onReady: function( param ) {
		this.maxW = $('.article').innerWidth();
		this.fixImages();
		this.advChange();

	},


	/**
	 * 修复图片被压缩问题
	 * @return {[type]} [description]
	 */
	fixImages: function() {
		$('.mp-cimage').each(( idx, em)=>{
			if ( $(em).attr('width') > this.maxW ) {
				$(em).attr('height', '');
				$(em).css('height', '');
			}
		});
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