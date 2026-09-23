# FizeProviderRegion

行政区划数据访问组件。项目将行政区划数据预置为 SQLite 数据库，并通过统一的处理器接口提供省、市、区、街道和社区（村）数据查询，不需要在运行时访问网络。

## 功能概览

- 使用 Composer 的 PSR-4 自动加载。
- 通过 `RegionFactory` 或具体处理器类创建查询实例。
- 按父级编码查询下一级行政区划列表。
- 根据行政区划编码生成完整名称，并支持自定义分隔符和名称清理规则。
- 内置国家统计局（NBS）和民政部（MCA）两份数据快照。
- 保留旧的 Local 数据处理器源码；Local 已标记为弃用，当前接口并不完整。

## 数据源与层级

| 处理器 | 常量 | 数据库 | 支持层级 | 数据来源 / 说明 |
| --- | --- | --- | --- | --- |
| NBS | `RegionHandler::NBS` | `database/NBS.sqlite3` | 5 级：省、市、区、街道、社区（村） | 国家统计局行政区划代码（当前更新脚本使用 2023 页面） |
| MCA | `RegionHandler::MCA` | `database/MCA.sqlite3` | 3 级：省、市、区 | 民政部行政区划数据（当前更新脚本使用 2022 页面） |
| Local | `RegionHandler::LOCAL` | `database/Local.sqlite3` | 3 级：省、市、区（接口未完成） | 历史本地数据，来源不明，已弃用 |

NBS 数据源页面：<https://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/index.html>

MCA 处理器的 `getTowns()` 和 `getVillages()` 当前固定返回空数组；需要街道和社区数据时请选择 NBS。

## 环境要求

- PHP `>= 7.0`
- PHP 扩展 `sqlite3`
- Composer

开发和运行更新脚本还需要 `dom`、`json`、`libxml` 扩展。生产环境只使用查询接口时，安装普通依赖即可。

## 安装

```bash
composer require fize/provider-region
```

项目中的 `database/*.sqlite3` 会随包提供。处理器以只读方式打开这些文件，应用进程不需要数据库写权限。

## 快速开始

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Fize\Provider\Region\RegionFactory;
use Fize\Provider\Region\RegionHandler;

$handler = (new RegionFactory())->create(RegionHandler::NBS);

// 查询省列表
foreach ($handler->getProvinces() as $province) {
    echo $province->id . ': ' . $province->name . PHP_EOL;
}

// 根据父级编码查询下一级
$cities = $handler->getCitys(35);       // 福建省
$countys = $handler->getCountys(3505);  // 泉州市
$towns = $handler->getTowns(350521);    // 惠安县
$villages = $handler->getVillages(350521111); // 东桥镇

// 根据编码生成完整名称
echo $handler->getFullName(350521111219) . PHP_EOL;
// 福建省泉州市惠安县东桥镇莲塘村委会

echo $handler->getFullName(110109, '-', 1) . PHP_EOL;
// 北京市-门头沟区
```

也可以直接实例化处理器：

```php
use Fize\Provider\Region\Handler\MCA;

$handler = new MCA();
$name = $handler->getFullName(110109, '-');
// 北京市-市辖区-门头沟区
```

## 统一接口

所有处理器都继承 `RegionHandlerInterface`，主要方法如下：

| 方法 | 返回值 | 用途 |
| --- | --- | --- |
| `getProvinces()` | `RegionItem[]` | 获取顶级省级行政区 |
| `getCitys(int $provinceId)` | `RegionItem[]` | 获取指定省的市级行政区 |
| `getCountys(int $cityId)` | `RegionItem[]` | 获取指定市的区县级行政区 |
| `getTowns(int $countyId)` | `RegionItem[]` | 获取指定区县的街道乡镇（NBS） |
| `getVillages(int $townId)` | `RegionItem[]` | 获取指定街道的社区或村（NBS） |
| `getFullName(int $id, string $separator = '', int $adjust = 0)` | `string` | 根据编码拼接完整名称 |
| `get(int $id)` | `array` | 按编码返回该节点及其上级节点 |

`RegionItem` 的常用公开字段：

| 字段 | 含义 |
| --- | --- |
| `id` | 行政区划编码 |
| `pid` | 父级行政区划编码 |
| `name` | 行政区划名称 |
| `level` | 层级，NBS 使用 1 至 5 |
| `shortName` | 简称，仅 Local 数据包含 |

不存在的编码会返回空列表或空字符串；列表结果按数据库中的 `sort` 字段排序。

## `getFullName()` 的调整规则

`$adjust` 用于处理“市辖区”“县”等名称：

- `0`：保留原始层级名称。
- `1`：移除“市辖区”“县”“直辖县级”。
- `2`：在 `1` 的基础上移除路径中间重复的市级名称。

例如：

```php
$handler->getFullName(350581, '-', 0); // 福建省-泉州市-石狮市
$handler->getFullName(350581, '-', 2); // 福建省-石狮市
```

## 数据更新

更新代码位于 `tests/Updater/`，目前作为维护脚本和测试辅助代码存在，不是公开运行时 API：

- `tests/Updater/NBS.php`：抓取国家统计局 2023 页面，使用 `.cache/` 缓存分级页面，重建 `database/NBS.sqlite3`。
- `tests/Updater/MCA.php`：抓取民政部 2022 页面，重建 `database/MCA.sqlite3`。

更新脚本需要网络访问，以及 `dom`、`json`、`libxml`、`sqlite3` 扩展。执行前请备份对应 SQLite 文件；脚本会清空并重建 `region` 表，NBS 脚本还会重建 `village` 表。页面结构或来源地址变化时，需要同步调整脚本。

## 项目结构

```text
src/
├── Handler/                 # Local、MCA、NBS 处理器
├── RegionFactory.php        # 处理器工厂
├── RegionHandler.php        # 处理器常量
├── RegionHandlerInterface.php
└── RegionItem.php           # 行政区划项
database/                    # 随项目发布的 SQLite 快照
tests/Handler/               # 处理器测试
tests/Updater/               # 数据更新脚本
```

## 当前限制

- `RegionItem::getId()`、`getPid()`、`getFullName()` 和 `getExtend()` 目前是占位实现；请直接读取公开字段，使用处理器的 `getFullName()`。
- `get()` 的返回数组在不同处理器中的排列方式并不完全一致，跨数据源使用时应以具体处理器实现为准；需要稳定的层级列表时，优先使用 `getProvinces()` 至 `getVillages()`。
- `src/Handler/Local.php` 没有实现接口要求的 `getTowns()` 和 `getVillages()`，因此当前会保持为抽象类，不能直接实例化。
- 处理器构造函数接收的配置数组当前只保存，不会改变数据库路径或查询行为。
- 数据库是随包发布的静态快照，行政区划调整后需要重新生成并发布数据库文件。

## 测试

PHPUnit 默认的文件后缀规则不会自动发现本项目的 `Test*.php` 文件。可以显式运行不访问外部网络的测试：

```bash
vendor/bin/phpunit tests/Handler/TestNBS.php
vendor/bin/phpunit tests/Handler/TestMCA.php
vendor/bin/phpunit tests/TestRegionFactory.php
```

`tests/Handler/TestLocal.php` 当前会因 Local 类接口未完成而失败；更新相关测试会访问外部网站，网络不可用时也应跳过 `tests/Updater/`。

## 许可证

本项目使用 [MIT License](LICENSE)。
