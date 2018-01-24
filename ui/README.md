可信云 2.0 UI 界面
==============================

## 一、页面

### 1.1 桌面页面

| 页面 | 中文名  | 入口地址 | 兼容映射 |
| --- | --- |  --- | --- |
| /desktop/index | 可信云首页  | / | mobile: /mobile/index   |
| /desktop/service/list | 云服务列表页  | /service | mobile: /mobile/service/list   |
| /desktop/service/detail | 云服务详情页  | /service/detail/{id:\\d+} | mobile: /mobile/service/detail   |
| /desktop/article/list | 新闻列表页  | /article | mobile: /mobile/article/list  |
| /desktop/article/detail | 新闻详情页  | /article/{id:\\d+} | mobile: /mobile/article/detail  |
| /desktop/mix | 混合云  | /mixcloud | mobile: /mobile/mix  |
| /desktop/open | 开源解决方案  | /opensource | mobile: /mobile/open  |


### 1.2 手机页面

| 页面 | 中文名  | 入口地址 | 兼容映射 |
| --- | --- |  --- | --- |
| /mobile/index | 可信云首页(手机版)  | /m | desktop: /desktop/index   |
| /mobile/service/list | 云服务列表页(手机版)  | /m/service | desktop: /desktop/service/list   |
| /mobile/service/detail | 云服务详情页(手机版)  | /m/service/detail/{id:\\d+} | desktop: /desktop/service/detail   |
| /mobile/article/list | 新闻列表页(手机版)  | /m/article | desktop: /desktop/article/list  |
| /mobile/article/detail | 新闻详情页(手机版)  | /m/article/{id:\\d+} | desktop: /desktop/article/detail  |
| /mobile/mix | 混合云(手机版)  | /m/mixcloud | desktop: /desktop/mix  |
| /mobile/open | 开源解决方案(手机版)  | /m/opensource | desktop: /desktop/open  |


## 二、通用页面

| 页面 | 说明 |
| --- | --- |
| /desktop/common/header.page | 桌面头部  |
| /desktop/common/footer.page | 桌面尾部  |
| /desktop/common/nav.page | 桌面导航  |
| /mobile/common/header.page | 手机头部  |
| /mobile/common/footer.page | 手机尾部  |
| /mobile/common/nav.page | 手机导航  |


## 三、静态文件

| 目录 | 说明  |
| --- | --- |
| /desktop/assets/js | 桌面第三方JS库  |
| /desktop/assets/css | 桌面第三方CSS  |
| /desktop/assets/images | 桌面静态图片  |
| /mobile/assets/js | 手机第三方JS库  |
| /mobile/assets/css | 手机第三方CSS  |
| /mobile/assets/images | 手机静态图片  |


## 四、存储配置

```json
...
"storage": {
	"engine":"minapages",
	"options":{
		"debug":true,
		"server":"<your host>",
		"url":"/static-file/kexinyun",
		"origin":"/static-file/kexinyun",
		"prefix":"/kexinyun",
		"appid": "xxx.sl2",
		"secret":"xxxjdjs"
	},
	"pages":{
		"remote": "/kexinyun/pages",
		"url": "/static-file/kexinyun/pages",
		"origin": "/static-file/kexinyun/pages"
	},
	"binds": [
		{
			"local": "/web/desktop/assets",
			"remote": "/kexinyun/web/assets",
			"url": "/static-file/kexinyun/web/assets",
			"origin": "/static-file/kexinyun/web/assets"
		},
		{
			"local": "/web/mobile/assets",
			"remote": "/kexinyun/mob/assets",
			"url": "/static-file/kexinyun/mob/assets",
			"origin": "/static-file/kexinyun/mob/assets"
		}
	]
}
...




```


## 五、 页面分享

```bash
#私有云
链接:  https://www.kexinyun.org/m/opensource  
图片: https://www.kexinyun.org/static-file/kexinyun/mob/assets/images/fn1.png
标题:  可信云开源解决方案评估介绍
描述: 可信云开展了以OpenStack、Docker等代表的开源技术私有云解决方案评估。透过评测结果，帮助企业全面了解既定解决方案，节省方案选型时间。

```

```bash
#混合云
链接:  https://www.kexinyun.org/m/mixcloud
图片:  https://www.kexinyun.org/static-file/kexinyun/mob/assets/images/hhy1.png
标题:  可信云混合云解决方案评估介绍
描述: 可信云混合云解决方案评估，全面覆盖公有云服务商和私有云服务商，详细披露企业信息、解决方案质量、服务指标的完备性和规范性等内容

```

```bash
#首页
链接: https://www.kexinyun.org/m
图片: https://www.kexinyun.org/static-file/kexinyun/mob/assets/images/fea/kxy.png
标题: 选购云服务，先上可信云
描述: 独家公布百家主流云厂商、百余款云服务可信云评测数据。从可数据安全、服务质量、服务性能、运维管理和权益保障多维度透析云服务。
```

```bash
链接: https://www.kexinyun.org/m/article/{{id}}
标题: 新闻标题
图片: 新闻图片 {{cover}}
描述: {{summary}}
```

