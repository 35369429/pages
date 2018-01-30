import xpm from './xpmjs/xpm.rn';
// let host = 'wss.xpmjs.com';
let host = 'demo.xpmsns.com';
let option = {
	'app':1,
	'host':host,
	'https':host,
	'wss': host + '/ws-server',
	'table.prefix': '{none}',
	"appid": "wx0550a96041cf486c",
	// "secret":"151187416275946|0516fa148c2584028ee4a30157bfdc27",
	"secret":"151608778964451|ea972771da7d22d3266bf7135000e5e9",
	"user":"/xpmsns/user/user/wxappLogin"
}

xpm.option(option);
export default xpm;
