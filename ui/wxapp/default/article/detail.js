/**
 * MINA Pages 模板 （ Detail )
 */

// import * as faker from  '../common/faker.js';
// import Vdom from '../common/vdom.js'
let app = getApp();

Page({
	data:{ rs:{}, system:{}, loading:'' },

	onShareAppMessage: function(res) {

		let that = this;
		return {
			title:that.data.rs.title,
			path:'/default/article/detail?id=' + that.data.rs.article_id,
			success: function(res) {
				wx.showToast({
					title: '转发成功',
					icon: 'success',
					duration: 1000
				})
			}
		}
	},

	demo: function(){
		wx.redirectTo({url:'/default/article/recommend'});
	},

	getArticle: function( article_id ) {

		let xpm = app.xpm;
		let $get = xpm.api('/xpmsns/pages/article/get');

		return new Promise((resolve, reject)=>{
	    	$get().get({articleId:article_id}).then(( article )=>{

	    		console.log( article );

	    		this.done();
	    		this.setData({rs:article});
	    		resolve(article);
	    	}).catch( (excp) => {
	      		// 读取数据失败
	      		console.log( 'excp:',  excp );
	    	});
    	});

		// let that = this;
		// return new Promise( function( resolve, reject ) {
		// 	setTimeout(function(){

		// 		let data = faker.detail;
		// 			data['page'] = data['page'] || 'no';
		// 			data['system'] = that.system;
		// 		that.setData( {data:data} );
		// 		that.done();
		// 		resolve(data);
					
		// 	}, 0);
		// });
	},

	onLoad: function( params ) {

		if ( typeof params['id'] == 'undefined') {
			this.error('未知文章ID, 非法请求');
		}
		let data = {};
		data['page'] = 'default';
		data['system'] = wx.getSystemInfoSync();
		this.setData({system:data['system'], data:data});
		this.getArticle( params['id'] );

	},

	done: function() {
		this.setData({loading:'display:none'});
	},

	error: function( message, title="出错啦" ) {
		wx.showModal({
			title:title,
			content: message,
			showCancel:false,
			success: function(res) {
				wx.navigateBack();
			}
		});
	}


})
