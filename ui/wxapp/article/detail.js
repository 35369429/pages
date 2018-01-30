/**
 * MINA Pages 模板 （ List )
 *
 *
 */

import * as faker from  '../common/faker.js';
// import Vdom from '../common/vdom.js'

Page({
	data:{ data:{}, system:{}, loading:'' },

	onShareAppMessage: function(res) {

		let that = this;
		return {
			title:that.data.data.title,
			path:'/article/detail?id=' + that.data.data.id,
			success: function(res) {
				wx.showToast({
					title: '转发成功',
					icon: 'success',
					duration: 1000
				})
			}
		}
	},

	getArticle: function( id ) {

		let that = this;
		return new Promise( function( resolve, reject ) {
			setTimeout(function(){

				let data = faker.detail;
					data['page'] = data['page'] || 'no';
					data['system'] = that.system;
				that.setData( {data:data} );
				that.done();
				resolve(data);
					
			}, 0);
		});
	},

	onLoad: function( params ) {

		if ( typeof params['id'] == 'undefined') {
			this.error('未知文章ID, 非法请求');
		}
		let data = {};
		data['page'] = 'default';
		data['system'] = wx.getSystemInfoSync();
		this.setData({system:data['system'], data:data});
		this.getArticle( params['id'] ).then();

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
