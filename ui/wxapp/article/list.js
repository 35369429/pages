/**
 * MINA Pages 模板 （ List )
 *
 *
 */

import * as faker from  '../common/faker.js';

Page({

	data: { 
		categories:[], articles:[], system:{},
		status:{lock:true, refresh:'', more:'hidden' },
		pagination: {curr:1, next:false, prev:false },
		category:0

	},

	detail: function( event ) {
		let data = event.target.dataset;
		wx.navigateTo({url:'detail?id=' + data.id});
		console.log( data );
	},

	onShareAppMessage: function(res) {

		res = res || {};
		res['from'] = res['from'] || 'unknown';
		if (res.from === 'button') {
			console.log(res.target)
		}

		let cid = this.data.categories[this.data.category]['id'];
		let title = this.data.categories[this.data.category]['name'] + ' - MINA Pages';

		return {
			title:title,
			path:'/article/list?cid=' + cid,
			success: function(res) {
				wx.showToast({
					title: '转发成功',
					icon: 'success',
					duration: 1000
				})
			}
		}
	},

	refreshArticles() {

		if ( this.isLoading() === true ) {
			return ;
		}

		let that = this;
		let cid = this.data.categories[this.data.category]['id'];
		that.refresh();
		that.getArticles( 1, cid )
			.then(function( articles ) {
				that.replaceArticles( articles );
				that.done();
			})
	},

	/**
	 * 载入指定分类的文章
	 * @param  {[type]} event [description]
	 * @return {[type]}       [description]
	 */
	loadArticlesByCate: function( event ) {
		let that = this;
		let data = event.target.dataset;

		if ( that.data.category == data.id ) return;
		let categories = that.data.categories;
		let curr = that.data.category;
			categories[curr]['class'] = '';
			categories[data.id]['class'] = 'active';

		let title = categories[data.id]['name'];
			title = title + ' - MINA Pages';
			wx.setNavigationBarTitle({title: title});

		that.loading();
		that.setData({articles:[], categories:categories, category:data.id});
		that.getArticles( 1, data.cid )
			.then(function( articles ) {
				that.replaceArticles( articles );
				that.done();
			})
	},


	/**
	 * 装载下一页内容
	 * @param  {[type]} event [description]
	 * @return {[type]}       [description]
	 */
	loadNextPage: function( event ) {

		if ( this.isLoading() === true ) {
			return ;
		}

		if (this.data.pagination.next === false ) {
			return;
		}

		let that = this;
		that.loading();
		that.getArticles( this.data.pagination.next )
			.then(function( articles ) {

				that.pushArticles( articles );
				that.done();
			})

	},

	replaceArticles: function( articles ) {
		this.setData({articles:articles});
	},

	pushArticles: function( articles ) {

		let articles_origin = this.data.articles;

		for( let i in articles ) {
			articles_origin.push(articles[i]);
		}

		this.setData({articles:articles_origin});


	},


	/**
	 * 读取文章分类
	 * @return 
	 */
	getCategoris: function( cid ) {

		let that = this;

		return new Promise(function( resolve, reject ) {

			let categories = faker.categories;
			for( var idx in categories ) {
				if ( categories[idx]['class'] == 'active' )  {
					let title = categories[idx]['name'];
						title = title + ' - MINA Pages';
					that.setData({category:idx});
					wx.setNavigationBarTitle({title: title});
				}
			}

			resolve(faker.categories);
		});
	},


	/**
	 * 读取文章
	 * @param  {[type]} cateid [description]
	 * @return {[type]}        [description]
	 */
	getArticles: function( page, cateid ) {

		page = page || 1;
		cateid = cateid || null;
		let that = this;

		return new Promise(function( resolve, reject ) {

			setTimeout(function(){
				that.done();

				if ( page == 1) {
					that.setData({pagination:{curr:1, next:2, prev:false}});
				} else {
					that.setData({pagination:{curr:2, next:false, prev:1}});
				}

				resolve(faker["articles-" + page]);
			}, 2000);

		});
	},



	onLoad: function ( params ) {
		let that = this;
		let cid = params['cid'] || "";
		that.setData({system:wx.getSystemInfoSync()});

		Promise.all([that.getCategoris(cid), that.getArticles(1, cid) ] )

		.then( function( resp ) {

			that.setData({
				"categories": resp[0],
				"articles" : resp[1],
			});

		}).catch(function( error ) {
			console.log( 'someting error');

		})
	},

	isLoading: function() {
		return this.data.status.lock;
	},

	refresh: function(){
		let status = this.data.status;
		status['refresh'] = '';
		status['lock'] = true;
		this.setData({status:status});
	},

	loading: function() {
		let status = this.data.status;
		status['more'] = '';
		status['lock'] = true;
		this.setData({status:status});
	},

	done: function() {
		let status = this.data.status;
		status['more'] = 'hidden';
		status['refresh'] = 'hidden';
		status['lock'] = false;
		this.setData({status:status});
	}
})





















