/**
 * XpmSNS头条模板 （ List )
 */
let app = getApp();
Page({

	data: { 
		categories:[], articles:[], system:{},
		status:{lock:true, refresh:'', more:'hidden' },
		pagination: {curr:1, next:false, prev:false },
		category:0

	},

	detail: function( event ) {
		let data = event.currentTarget.dataset;
		wx.navigateTo({url:'detail?id=' + data.id});
		console.log( data );
	},

	onShareAppMessage: function(res) {

		res = res || {};
		res['from'] = res['from'] || 'unknown';
		if (res.from === 'button') {
			console.log(res.target)
		}

		let cid = this.data.categories[this.data.category]['category_id'];
		let title = this.data.categories[this.data.category]['name'] + ' - 国家医管中心';

		return {
			title:title,
			// path:'/default/article/recommend?cid=' + cid + '&index=' + this.data.category,
			path:'/default/article/recommend',
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
		let cid = this.data.categories[this.data.category]['category_id'];
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
			title = title + ' - 国家医管中心';
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

		let cid = this.data.categories[this.data.category]['category_id'];
		let that = this;

		that.loading();
		that.getArticles( this.data.pagination.next, cid )
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

		let xpm = app.xpm;
		let $search = xpm.api('/xpmsns/pages/category/search');

		return new Promise( (resolve, reject) => {
			$search().get({perpage:40, page:1, param:'isnav=true', order:'priority asc'}).then(( cates )=>{
				
				let total = cates.total || 0;
				let categories = cates.data;
				let current = null;
				let title = null;

				if ( total == 0 ) {
					console.log('someting Error', cates );
					reject(excp);
					return;
				}

				for( var idx in categories ) {
					categories[idx]['class'] = "";
					if ( categories[idx]['class'] == 'active' )  {
						current = idx;
					}
					if (  categories[idx]['category_id']  == cid  ) {
						current = idx;
					}
				}

				if ( current == null ) {
					current = 0 ;
				}

				cid = categories[current]['category_id'];
				title = categories[current]['name']  + " - 国家医管中心";
				categories[current]['class'] = 'active';
				this.setData({category:current});
				wx.setNavigationBarTitle({title: title});
				resolve(categories);

			}).catch( (excp) => {
				console.log('someting Error', excp );
				reject(excp);
			});
		});
	},


	/**
	 * 读取文章
	 * @param  {[type]} cateid [description]
	 * @return {[type]}        [description]
	 */
	getArticles: function( page, cid ) {

		page = page || 1;
		cid = cid || null;
		let xpm = app.xpm;
		let $search = xpm.api('/xpmsns/pages/article/search');
      	return new Promise( (resolve, reject) => {
	      	$search().get({page:page, perpage:15, categoryId:cid, order:'publish_time desc'})
	      	.then((articles)=>{
	      		this.setData({pagination:articles});
	      		for( var idx in articles['data'] ) {
	      			articles['data'][idx]['template'] = 'image';  // 根据数据选择呈现样式
	      		}

	      		this.done();
	      		resolve( articles['data'] );
	      	})
	      	.catch(( excp )=>{
	      		this.done();
	      		console.log('someting error', excp);
	      	})
      	});
	},

	onLoad: function ( params ) {

		let cid = params['cid'] || "";
		this.setData({system:wx.getSystemInfoSync()});
		if ( cid == "" ) {

			this.getCategoris(cid).then((cates)=>{
				this.setData({categories:cates});
				let idx = this.data.category;
				cid = cates[idx]['category_id'];
				return cid;
			}).then( (cid) =>{
				return this.getArticles(1, cid );
			}).then( (articles ) =>{
				this.setData({articles:articles, "fst" : articles[0]});
			}).catch(function( excp ) {
				console.log( 'someting error', excp);
			});

		} else {

			Promise.all([this.getCategoris(cid), this.getArticles(1, cid) ] )

			.then( ( resp ) => {
				console.log( resp );

				this.setData({
					"categories": resp[0],
					"articles" : resp[1],
					"fst" : resp[1]['data'][0]
				});
			}).catch(function( excp ) {
				console.log( 'someting error', excp);
			})
		}

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





















