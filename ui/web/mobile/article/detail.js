
let web = getWeb();
Page({
	data:{},
	onReady: function( params ) {

		console.log( params, this.data );



		// var that =this;
		// $('title').html(that.data.aridata.title);
		// $('#newsdata').addClass('on').parents('li').removeClass('on');
		// renderMathInElement(document.body);

		// var ua = window.navigator.userAgent.toLowerCase(); 
		// if(ua.match(/MicroMessenger/i) == 'micromessenger'){
		//     $.getScript("//res.wx.qq.com/open/js/jweixin-1.2.0.js",function(){
		//         var logo = window.location.origin + that.data.aridata.cover;

		//         var href =  window.location.href;
		//         var dataurl ="/_api/xpmsns/pages/article/signdata?url="+href;
		//         $.get(dataurl, function(data) {
		//             wx.config({
		//                 debug: false, 
		//                 appId:data['appid'], // 必填，公众号的唯一标识
		//                 timestamp:data['timestamp'] , // 必填，生成签名的时间戳
		//                 nonceStr:data['noncestr'], // 必填，生成签名的随机串
		//                 signature:data['signature'],// 必填，签名，见附录1
		//                 jsApiList:  ['onMenuShareTimeline','onMenuShareAppMessage','onMenuShareQQ','onMenuShareWeibo','onMenuShareQZone','startRecord','stopRecord','onVoiceRecordEnd','playVoice','pauseVoice','stopVoice','onVoicePlayEnd','uploadVoice','downloadVoice','chooseImage','previewImage','uploadImage','downloadImage','translateVoice','getNetworkType','openLocation','getLocation','hideOptionMenu','showOptionMenu','hideMenuItems','showMenuItems','hideAllNonBaseMenuItem','showAllNonBaseMenuItem','closeWindow','scanQRCode','chooseWXPay','openProductSpecificView','addCard','chooseCard','openCard'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
		//             });  
		//             wx.ready(function(){
		//                 wx.onMenuShareTimeline({
		//                     title:that.data.aridata.title, // 分享标题
		//                     link: data['url'], // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
		//                     imgUrl:logo, // 分享图标
		//                     success: function () { 
		//                         // 用户确认分享后执行的回调函数
		//                         alert('分享成功');

		//                     },
		//                     cancel: function () { 
		//                         // 用户取消分享后执行的回调函数
		//                         alert('分享失败');
		//                     }
		//                 });
		//                 wx.onMenuShareAppMessage({
		//                     title: that.data.aridata.title, // 分享标题
		//                     desc: that.data.aridata.summary, // 分享描述
		//                     link: data['url'], 
		//                     imgUrl: logo, // 分享图标
		//                     success: function () { 
		//                         // 用户确认分享后执行的回调函数
		//                         alert('分享成功');
		//                     },
		//                     cancel: function () { 
		//                         // 用户取消分享后执行的回调函数
		//                           alert('分享失败');
		//                     }
		//                 });

		//             });
		        

		//         },'json');
		//     })
		// }


	},

	hello: function ( event ) {
	},

	world: null
})