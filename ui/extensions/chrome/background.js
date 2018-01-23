
function alert( title, message ) {
	var opt = {
			type: "basic",
			title: title,
			message: message,
			iconUrl: "icon.png"
		}
	chrome.notifications.create(null,opt);
}

function getHome( value ){
	let url = value;
	if ( url.slice(-4) != '/_a/' && url.slice(-3) != '/_a' ) {
		url = url + '/_a';
	}

	return url
}


function getConfig( callback ) {

	chrome.storage.local.get(null, (items) => {
		console.log('getConfig items', items);
		callback(chrome.runtime.lastError ? null : items);
	});	
}


function fwd(link) {

	// 从Store里读取配置信息
	getConfig(( config )=>{
		let host = config['host'] || "";
		if ( host == "" ) {
			alert('未设定XpmSNS后台地址', '请填写后台地址后操作');
			return;
		}

		let qk =config['quickly'] ?  1 : 0;
		let pb =config['published'] ?  1 : 0;
		let url = getHome(host) + '/i/xpmsns/pages/article/collect';
			url = url + '?url=' + escape(link) + '&quickly=' + qk + '&published=' + pb;
			chrome.tabs.create({url: url });
	});				 
}


chrome.contextMenus.create({
	title: "转采本页", 
	contexts:["page"], 
	onclick: function(info, tab){
		fwd(info.pageUrl);
	}
});

chrome.contextMenus.create({
	title: "转采链接页面", 
	contexts:["link"], 
	onclick: function(info, tab){
		fwd(info.linkUrl);
	}
});