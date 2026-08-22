<p align="center">
    <a href="README.md"><img src="https://img.shields.io/badge/LANG-English-blue"></a>
    <a href="README_cn.md"><img src="https://img.shields.io/badge/语言-简体中文-red"></a>
</p>

# CS2 WeaponPaints 配置管理网站

> 面向私人 CS2 社区服的中英双语免 Steam 登录配置管理网站。

**本项目不需要 Steam 登录。** 玩家只需使用 Steam64 ID 创建或选择配置，即可在网页中调整饰品。

本项目适合私人服务器和可信任的小型玩家群体，不是面向公开网站的完整用户注册系统。

## 界面

<p align="center">
    <img src="./preview/img/1.png" width="45%">
    <img src="./preview/img/2.png" width="45%">
</p>

<p align="center">
    <img src="./preview/img/3.png" width="45%">
    <img src="./preview/img/4.png" width="45%">
</p>

<p align="center">
    <img src="./preview/img/5.png" width="45%">
    <img src="./preview/img/6.png" width="45%">
</p>

## 主要功能

* 通过 Steam64 ID 管理配置，无需 Steam 登录
* 支持全局、T 阵营和 CT 阵营三种编辑模式
* 支持武器、匕首、手套、探员、音乐盒、CS2 徽章和武器挂件
* 支持磨损、模板、名称标签、StatTrak™ 状态和击杀数
* 每把武器提供 5 个贴纸槽位，支持全部覆盖、全部清除和单槽位磨损、位置、缩放、旋转设置
* 支持挂件模板和 X/Y 偏移设置
* 支持搜索皮肤、贴纸、挂件、音乐盒和徽章
* 支持实验性皮肤融合，可将涂装应用到其他武器或匕首
* 支持网站访问密码、单配置 PIN 和管理员模式
* 支持通过右上角“连接至服务器”按钮直接调用 Steam，或复制带密码的游戏内控制台指令
* 管理员可以管理和删除所有配置，并在线调整站点名称、默认语言、默认主题、融合涂装开关、服务器地址和密码
* 支持英语和简体中文
* 支持在网页中切换浅色与深色主题、记住浏览器偏好，并提供本地图片兜底

## 运行要求

* PHP 8.0 或更高版本，并启用 Session 和 PDO MySQL
* MySQL 或 MariaDB
* 已正常运行并连接到同一数据库的 [WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints) 插件

建议启用 PHP cURL 和 mbstring。数据库账号应拥有 `SELECT`、`INSERT`、`UPDATE`、`DELETE`、`CREATE` 和 `ALTER` 权限。

## 安装方法

1. 将项目复制到网页服务器的网站根目录，或者服务器已配置的网站目录中。

2. 编辑 `config.php`：

   ```php
   <?php
   define('DEFAULT_LANGUAGE', 'en'); // 可用值：en、zh-CN
   define('DEFAULT_WEB_THEME', 'dark'); // 可填写 dark 或 light；访客可在网页中切换
   define('SITE_NAME_EN', 'CS2 Loadout Manager'); // 英文名称，同时作为回退名称
   define('SITE_NAME_ZH_CN', 'CS2 饰品管理器'); // 简体中文名称
   define('AUTH_RATE_LIMIT_ATTEMPTS', 5); // 时间窗口内允许的失败次数
   define('AUTH_RATE_LIMIT_WINDOW_SECONDS', 1800); // 失败次数统计窗口，单位为秒
   define('AUTH_RATE_LIMIT_LOCK_SECONDS', 60); // 触发限制后的锁定时间，单位为秒
   define('ENABLE_SKIN_FUSION', true); // 允许跨武器组合涂装

   define('SERVER_ADDRESS', ''); // 填写域名或 IP 与端口；留空则隐藏“连接至服务器”按钮
   define('SERVER_PASSWORD', ''); // 留空时直接启动 CS2；填写后改为复制游戏内控制台指令
   define('SITE_ACCESS_PASSWORD', ''); // 设置密码以启用网站访问保护
   define('ADMIN_PASSWORD', ''); // 留空表示禁用管理员模式

   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

3. 访问网站：

   ```text
   http://你的服务器/网站目录/
   ```

只要数据库账号拥有 `CREATE` 和 `ALTER` 权限，网站会自动创建辅助数据表并补充缺少的辅助字段。

## 使用方法

1. 使用 Steam64 ID 和可选的备注用户名新建配置。
2. 可以在新建时为配置启用 PIN。
3. 打开配置并选择全局、T 或 CT 编辑模式。
4. 选择需要的饰品，通过“编辑”调整磨损、模板、StatTrak™、名称标签、贴纸和挂件参数。
5. 在编辑弹窗中按“保存”应用设置。贴纸选择和贴纸高级参数使用各自独立的保存流程。

全局模式会将支持阵营区分的设置同时写入 T 和 CT。音乐盒只在全局模式下管理，探员则分别在 T 和 CT 模式下选择。

新建配置表单在 **Steam64 ID** 旁提供帮助按钮。普通 Steam 个人资料网址会直接包含数字形式的 Steam64 ID；设置了自定义个人资料网址时，可以使用帮助弹窗中的第三方解析链接。解析网站的地址和名称保存在 `class/translations.php` 的翻译字段中，无需修改弹窗结构即可替换。

### 融合涂装（实验性）

将 `ENABLE_SKIN_FUSION` 设置为 `true`，或由管理员在“站点设置”中启用融合涂装，然后打开武器的“皮肤”选择弹窗，再选择“融合涂装”，即可为当前武器或匕首选择其他涂装。融合后的实际效果取决于 WeaponPaints 插件版本和 CS2 的游戏行为。

### 配置 PIN

* 进入受保护的配置前需要输入 PIN。
* 验证成功后，当前浏览器会话内不需要重复输入。
* 可以在配置顶部的“基础信息”中修改或关闭 PIN。
* PIN 以密码哈希保存，无法从数据库中还原明文。
* 未启用 PIN 的配置可以被其他访问者编辑，对方也可以为其启用 PIN，因此请在分享网站前保护重要配置。

### 管理员模式

在 `config.php` 中填写 `ADMIN_PASSWORD` 后，网页右上角会启用管理员按钮。管理员可以跳过配置 PIN、进入任意配置、修改或清除配置 PIN，并删除配置。

进入管理员模式后，再次打开管理员按钮即可修改中英文站点名称、默认语言、默认主题、融合涂装开关、服务器地址和服务器密码。通过验证的值会直接写入 `config.php` 中对应的白名单常量；默认语言只影响尚未选择语言的访客，默认主题不会覆盖访客保存在浏览器中的主题偏好。

**所有配置都只能在管理员模式下删除。**

`SITE_ACCESS_PASSWORD` 仍然是进入网站的第一层保护，与管理员模式和配置 PIN 相互独立。

## 更新 CS2 数据

更新工具从 [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API) 获取皮肤、手套、探员、音乐盒、贴纸、挂件、徽章和融合涂装目录。生成的 `data/paint_kits_en.json` 与 `data/paint_kits_zh-CN.json` 会保存融合选择器使用的涂装来源名称和图片。

### 首次运行

右键项目文件夹并复制地址，然后打开命令提示符或 PowerShell，切换到复制的文件夹地址：

```shell
cd "项目文件夹地址"
```

运行完整更新：

```bash
php tools/update_cs2_data.php
```

默认情况下，更新工具会清除对应的源缓存并完整获取全新数据。如果更新中途被打断，可以使用以下命令复用已经成功下载的缓存并继续：

```bash
php tools/update_cs2_data.php --resume
```

仅预览结果，不写入文件：

```bash
php tools/update_cs2_data.php --dry-run
```

只更新皮肤、手套和融合涂装目录：

```bash
php tools/update_cs2_data.php --only=skins
```

下载的源文件会保存在 `data/.source_cache/`。普通运行会在下载前清除对应缓存；使用 `--resume` 时会复用有效缓存，只下载缺失或无效的数据。替换数据前，旧文件会备份到 `data/backups/`。

如果 GitHub 返回 HTTP 429，请等待一段时间后使用 `--resume` 继续，避免再次下载已经完成的源文件。下载失败不会覆盖已经生成的数据文件。

## 数据库

网站会使用 WeaponPaints 已有的数据表，包括：

* `wp_player_skins`
* `wp_player_knife`
* `wp_player_gloves`
* `wp_player_agents`
* `wp_player_music`
* `wp_player_pins`

网站还会自动创建：

* `wp_presets`：保存配置列表、备注用户名和配置 PIN 哈希
* `wp_skin_settings_cache`：分别记住每款皮肤的磨损、模板、StatTrak™、名称标签、贴纸和挂件设置

## 安全说明

启用密码或 PIN 时请使用 HTTPS。网站访问密码、管理员密码和配置 PIN 的失败验证会按照客户端 IP 进行限流。默认规则是在 30 分钟内失败 5 次后锁定 1 分钟，可通过 `config.php` 中的 `AUTH_RATE_LIMIT_*` 设置调整。

所有会修改数据的请求均通过 CSRF 令牌校验进行保护。

本项目面向私人或可信任环境。移除 Steam 登录带来了便利，但网站无法验证访问者的真实 Steam 身份。

## 致谢

* [Nereziel/cs2-WeaponPaints](https://github.com/Nereziel/cs2-WeaponPaints)：提供 WeaponPaints 插件和原始网页工作流程
* [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API)：提供更新工具使用的 CS2 饰品数据
