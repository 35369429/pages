
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


function setConfig( key, value, callback ) {
	let items = {};
	items[key] = value;
	chrome.storage.local.set(items,	() => {
		callback(chrome.runtime.lastError ? false : true);
	});
}


document.addEventListener('DOMContentLoaded', () => {


	let host = document.getElementById('host');
	let quickly = document.getElementById('quickly');
	let loginBtn = document.getElementById('loginBtn');
	let fwdBtn = document.getElementById('fwdBtn');

	// 从Store里读取配置信息
	getConfig(( config )=>{
		host.value = config['host'] || "";
		quickly.checked = config['quickly'] || false;
	});


	// 设定 Host
	host.addEventListener('change', () => {
		setConfig('host', host.value ,(result)=>{
			console.log( result );
		});
	});


	// 设定快速转采
	quickly.addEventListener('change', () => {
		setConfig('quickly', quickly.checked ,(result)=>{
			console.log( result );
		});
	});


	// 登录按钮
	loginBtn.addEventListener('click', () => {
		
		if ( host.value == "" ) {
			alert('未设定XpmSNS后台地址', '请填写后台地址后操作');
			return;
		}

		let url = getHome(host.value);
		chrome.tabs.create({url: url });

	});


	// 转发按钮
	fwdBtn.addEventListener('click', () => {

		if ( host.value == "" ) {
			alert('未设定XpmSNS后台地址', '请填写后台地址后操作');
			return;
		}

		let qk = quickly.checked ?  1 : 0;
		let url = getHome(host.value) + '/n/xpmsns/pages/article/collect';
		let queryInfo = {
			active: true,
			currentWindow: true
		};

		chrome.tabs.query(queryInfo, (tabs) => {
			let tab = tabs[0];
			let u = tab.url;
			url = url + '?url=' + escape(u) + '&quickly=' + qk;
			chrome.tabs.create({url: url });
		});

	});

});
















