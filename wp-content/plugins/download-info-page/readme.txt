=== WP资源下载管理 ===
Contributors: wbolt,mrkwong
Donate link: https://www.wbolt.com/
Tags: Download, URL, Magnet Download, Thunder
Requires at least: 5.4
Tested up to: 5.8
Stable tag: 1.3.8
License: GNU General Public License v2.0 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP资源下载管理插件适用于资源下载类博客，支持站长发布文章时为访客提供本地下载、百度网盘及城通网盘等多种下载方式下载文章资源，并且支持设置登录会员或者评论回复后下载权限。

== Description ==

WP资源下载管理插件支持站长编写下载资源类文章时，通过上传下载资源到本地服务器，或者通过填写百度网盘、城通网盘分享链接，为读者提供下载。
* 上传文件-使用WordPress自带的媒体管理功能，直接上传分享资源到站点服务器，该方式可能受服务器上传文件格式及大小限制（大文件资源不建议使用此方式）。
* 城通网盘-考虑到部分站长使用该网盘分享资源给用户下载，通过城通网盘的返利联盟获得收益，在v1.3版本加入了这种下载方式。
* 百度网盘-站长只需在百度网盘等云存储工具生成分享链接，将分享地址复制到指定位置即可填入网盘下载链接和密码，发布即可。
* 磁力链接-支持Magnet磁力链接类资源，方便站长分享此类链接资源，以节省网站资源空间，一般为种子资源。
* 迅雷下载-支持迅雷支持的协议thunder://的资源链接。

WP资源下载管理插件在文章发布时提供两种下载方式：免费下载及回复后下载。
（1）免费下载即所有访客均可以直接下载资源；
（2）回复后下载即所有访客均需要对文章进行评论回复才可以下载；
（3）支持设置为付费下载，该功能需要安装<a href="https://www.wbolt.com/plugins/wp-vk?utm_source=wp&utm_medium=link&utm_campaign=dip" rel="friend" title="WordPress付费内容插件">WP付费内容插件</a>支持。

在使用WP资源下载管理插件前，你首先需要到 “WordPress 后台-插件-已安装的插件”，找到插件WP资源下载管理，点击启动；然后点击“设置”或者通过 “WordPress 后台-插件-WP资源下载管理”对插件进行配置：

1. 下载功能开关：开启开关后，即可在编辑文章时，使用WP资源下载管理，填入资源下载信息。
2. 是否需要登陆：开启开关后，则要求访客必须注册登录博客后才可以下载。
3. 下载浮层-支持配置页面滚动下拉时，下载按钮固定顶置或者固定位于顶部。
4. 版本说明-如果站长需要对下载资源进行版本说明，还可以添加版权说明文字。


== Installation ==

FTP安装
1. 解压插件压缩包download-info-page.zip，将解压获得文件夹上传至wordpress安装目录下的 `/wp-content/plugins/`目录.
2. 访问WordPress仪表盘，进入“插件”-“已安装插件”，在插件列表中找到“WP资源下载管理”，点击“启用”.
3. 通过“设置”->“WP资源下载管理” 进入插件设置界面.
4. 至此，该插件安装完毕，若在文章发布时填入资源链接即可实现资源分享下载功能。

仪表盘安装
1. 进入WordPress仪表盘，点击“插件-安装插件”：
* 关键词搜索“WP资源下载管理”，找搜索结果中找到“WP资源下载管理”插件，点击“现在安装”；
* 或者点击“上传插件”-选择“WP资源下载管理”插件压缩包download-info-page.zip，点击“现在安装”。
2. 安装完毕后，启用 `WP资源下载管理` 插件.
3. 通过“设置”->“WP资源下载管理” 进入插件设置界面.
4. 至此，该插件安装完毕，若在文章发布时填入资源链接即可实现资源分享下载功能。

关于本插件，你可以通过阅读<a href="https://www.wbolt.com/dip-plugin-documentation.html?utm_source=wp&utm_medium=link&utm_campaign=dip" rel="friend" title="插件教程">WP资源下载管理插件教程</a>学习了解插件安装、设置等详细内容。

== Frequently Asked Questions ==

= 插件是否支持付费下载？ =
不支持。但可以与<a href="https://www.wbolt.com/plugins/wp-vk?utm_source=wp&utm_medium=link&utm_campaign=dip" rel="friend" title="付费内容插件">付费内容插件</a>联合使用实现。

= 目前WP资源下载管理都支持哪些下载方式？ =

目前支持百度云、城通网盘、迅雷下载、磁力链接或WordPress上传资源管理，后期我们会考虑支持更多的资源类型。

= 为什么使用WP资源下载管理插件的上传文件会提示失败？ =

使用WP资源下载管理插件上传资源失败的原因可能是：
（1）上传的资源大小超出服务器限制；
（2）WordPress博客权限限制导致；
（3）服务器上传文件格式限制导致。

== Notes ==

<a href="https://www.wbolt.com/?utm_source=wp&utm_medium=link&utm_campaign=dip" rel="friend" title="WP资源下载管理插件">WP资源下载管理插件</a>开发的初衷是方便WordPress博主管理文章附件，通过网盘或者本地上传高效便捷地实现资源分享，为搭建WordPress下载类网站提供便利性。

闪电博（<a href='https://www.wbolt.com/?utm_source=wp&utm_medium=link&utm_campaign=dip' rel='friend' title='闪电博官网'>wbolt.com</a>）专注于原创<a href='https://www.wbolt.com/themes' rel='friend' title='WordPress主题'>WordPress主题</a>和<a href='https://www.wbolt.com/plugins' rel='friend' title='WordPress插件'>WordPress插件</a>开发，为中文博客提供更多优质和符合国内需求的主题和插件。此外我们也会分享WordPress相关技巧和教程。

除了百度搜索推送管理插件外，目前我们还开发了以下WordPress插件：

- [百度搜索推送管理-历史下载安装数100,000+](https://wordpress.org/plugins/baidu-submit-link/)
- [热门关键词推荐插件-最佳关键词布局插件](https://wordpress.org/plugins/smart-keywords-tool/)
- [Smart SEO Tool-高效便捷的WP搜索引擎优化插件](https://wordpress.org/plugins/smart-seo-tool/)
- [Spider Analyser – WordPress搜索引擎蜘蛛分析插件](https://wordpress.org/plugins/spider-analyser/)
- [WP VK-付费阅读/资料/工具软件资源管理插件](https://wordpress.org/plugins/wp-vk/)
- [IMGspider-轻量外链图片采集插件](https://wordpress.org/plugins/imgspider/)
- [博客社交分享组件-打赏/点赞/微海报/社交分享四合一](https://wordpress.org/plugins/donate-with-qrcode/)
- [清理HTML代码标签-一键清洗转载文章多余代码](https://wordpress.org/plugins/clear-html-tags/)

- 更多主题和插件，请访问<a href="https://www.wbolt.com/?utm_source=wp&utm_medium=link&utm_campaign=dip" rel="friend" title="闪电博官网">wbolt.com</a>!

如果你在WordPress主题和插件上有更多的需求，也希望您可以向我们提出意见建议，我们将会记录下来并根据实际情况，推出更多符合大家需求的主题和插件。

致谢！

闪电博团队

== Screenshots ==

1. 插件配置页面截图.
2. 文章编辑页面下载资源管理截图.
3. 多种下载方式下载按钮截图.
4. 需回复评论后下载界面截图.

== Changelog ==

= 1.3.8 =
* 兼容付费内容插件，支持下载价格设置。

= 1.3.7 =
* 兼容WordPress 5.7
* 解决与付费内容插件的兼容性问题；
* 部分地方微调。


= 1.3.6 =
* 修复与付费内容插件兼容性问题（可能会导致下载按钮不正常显示）；
* 优化下载复制密码方法，依赖clipboard js改为原生办法;
* 兼容无jquery引用主题;
* 其他兼容性细节优化。

= 1.3.5 =
* 新增付费内容插件兼容，实现付费下载；
* 新增磁力链接&迅雷下载两种下载方式；
* 新增插件版本更新提示功能；
* 优化下载地址区域展示样式。

= 1.3.4 =
* 修复快速编辑文章下载信息丢失bug；
* 修复文章编辑本地上传按钮失效bug；
* 优化浮动栏体验细节。

= 1.3.3 =
* 修复部分主题下插件前台无法正常显示bug；
* 部分后台设置体验优化。

= 1.3.2 =
* 修复文章快速编辑下载数据丢失bug；
* 部分样式微调优化；
* 其他已知问题优化解决。

= 1.3.1 =
* 增加城通网盘下载方式；
* 资源下载方式由原来的单一下载方式改为可多种下载方式；
* 进一步优化文章下载管理交互；
* 全新的下载按钮展示外观设计，可同时展示多种下载方式；
* 插件设置增加版权说明字段；
* 其他JS,SVG技术方案优化。

= 1.2.3 =
* 增加侧栏小工具可选择显示下载信息，配置页面增加显示下载次数选项
* 兼容闪电博主题

= 1.2.2 =
* 修复已知Bug

= 1.2.0 =
* 增加下载按钮位置选择，支持左对齐或者居中显示
* 增加下载浮层功能，支持下载按钮置顶或者底部固定浮层显示
* 增加回复评论下载功能，下载资源选择此项要求用户回复评论后下载
* 修复若干Bug

= 1.1.0 =
* 增加百度网盘分享链接快速识别填入功能
* 增加插件教程/插件支持等链接入口
* 优化文章发布资源分享字段描述
* 优化插件设置UI界面
* 修正下载插件弹窗移位问题

= 1.0.0 =
* 取消原有的中转页功能，提升资源下载用户体验
* 新增直接上传文件选项，为分享小文件资源提供便利
* 插件设置界面UI采用更规范统一的设计

= 0.2.1 =
* 增加必须会员才能下载功能选项

= 0.1.8 =
* 增加插件过虑器.

= 0.1.7 =
* 优化设置页面交互及更新演示缩略图.

= 0.1.6 =
* 修复设置功能的找不到页面的问题.

= 0.1.5 =
* 对设置界面参数设置进行重新分组，便于站长使用.

= 0.1.4 =
* 优化了插件设置界面，增加帮助内容，便于用户阅读和理解.
* 中转页增加插件版权信息.
* 更新了ready.text,插件信息更详细.

= 0.1.3 =
* 更新了设置界面各项设置说明