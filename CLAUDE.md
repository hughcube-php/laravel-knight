# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

Laravel Knight 是一个 Laravel 扩展包,提供增强的 Eloquent 模型、查询构建器、控制器工具、缓存机制和乐观锁等功能。项目基于 PHP 和 Laravel 框架开发。

## 开发命令

### 测试
```bash
# 运行所有测试
composer test
# 或
./vendor/bin/phpunit

# 运行单个测试文件
./vendor/bin/phpunit tests/Database/Eloquent/ModelTest.php

# 运行特定测试类中的方法
./vendor/bin/phpunit --filter testMethodName tests/SomeTest.php
```

### 代码质量检查
```bash
# 运行 PHPStan 静态分析
composer phpstan
# 或
./vendor/bin/phpstan analyse -vvv --memory-limit=-1

# 检查代码风格 (PSR2)
composer check-style
# 或
./vendor/bin/phpcs -p --standard=PSR2 src/ -v

# 自动修复代码风格
composer fix-style
# 或
./vendor/bin/phpcbf -p --standard=PSR2 src/ -v
```

### 安装依赖
```bash
composer install
```

## 架构说明

### 核心设计模式

1. **Trait 组合模式**: 项目大量使用 Trait 来组合功能,而不是深层继承。主要基类(Model、Job、Action)都通过组合多个 Trait 来提供功能。

2. **服务提供者注册**: `ServiceProvider` 类负责:
   - 注册 Mixin 到 Laravel 核心类(Collection、Builder、Grammar、Carbon)
   - 注册命令行工具
   - 注册动态路由(opcache、request、healthcheck、phpinfo、devops)
   - 配置自定义的 Auth User Provider

3. **缓存层设计**:
   - Model 通过 `getCache()` 方法返回 PSR SimpleCache 实例
   - 提供 `findById()`、`findByIds()` 等缓存查询方法
   - 自动监听 Eloquent 事件(created/updated/deleted/restored)清除缓存

### 主要模块

#### Database/Eloquent
- **Model**: 扩展 Laravel Eloquent Model,添加缓存、乐观锁等功能
- **Builder**: 扩展查询构建器,添加 `whereLike`、`whereLeftLike`、`whereRange` 等方法
- **Traits/KnightModelTrait**: 核心 Model trait,提供缓存和查询增强
- **Traits/OptimisticLock**: 乐观锁实现,依赖 `data_version` 字段

#### Queue
- **Job**: 抽象基类,使用 Dispatchable、Queueable、InteractsWithQueue 等 traits
- **FlowJobDescribe**: Job 流程描述和追踪
- 提供内置的实用 Jobs: PingJob、CleanFilesJob、ScheduleJob 等

#### Routing
- **Action** trait: 为控制器提供参数验证、缓存、事件分发等功能
- **Controller**: 扩展 Laravel Controller,使用 Action trait

#### Http/Middleware
提供多种中间件:
- 认证相关: Authenticate、CheckAuthUserInstanceOf、CheckAuthUserIsAvailable
- 环境限制: OnlyLocalGuard、OnlyProdEnvGuard、OnlyTestEnvGuard
- IP 限制: OnlyIpGuard、OnlyPrivateIpGuard、OnlyPublicIpGuard
- 其他: RequestSignatureValidate、LogRequest、HandleAllPathCors

#### OPcache
- 提供 OPcache 管理功能
- Commands: CompileFilesCommand、ClearCliCacheCommand、CreatePreloadCommand
- Actions: ScriptsAction、StatesAction (通过路由访问 opcache 状态)
- **CompileFilesCommand**: 默认缓存 app、config、routes、database 目录和所有 Composer PSR-4 自动加载文件

### 重要的 Trait

- **GetOrSet**: 提供缓存的 get/set/forget 方法
- **Validation**: 集成 Laravel Validation
- **ParameterBag**: 参数包管理
- **Container**: 访问 Laravel 容器
- **Logger**: 日志记录功能

### 配置文件

`config/knight.php` 控制功能路由前缀:
- `opcache.route_prefix`: OPcache 路由前缀
- `request.route_prefix`: 请求日志路由前缀
- `phpinfo.route_prefix`: PHPInfo 路由前缀
- `devops.route_prefix`: Devops 系统信息路由前缀

设置为 `false` 表示禁用相应路由。

### Mixin 扩展

项目通过 Mixin 扩展 Laravel 核心类:
- **CollectionMixin**: 扩展 Illuminate\Support\Collection
- **BuilderMixin**: 扩展数据库查询构建器
- **GrammarMixin**: 扩展 SQL 语法生成器
- **CarbonMixin**: 扩展 Carbon 日期时间
- **StrMixin**: 扩展 Str 辅助类(提供 `isCnCarLicensePlate()` 等中国特定验证)

### 测试策略

- 使用 Orchestra Testbench 进行包开发测试
- 测试文件位于 `tests/` 目录,镜像 `src/` 结构
- PHPUnit 配置: `phpunit.xml.dist`
- 内存限制设置为 2048M
- 使用 testing 数据库连接

## Git 提交规范

- 提交信息(commit message)中不得出现 AI、Claude、GPT 等相关字样
- 提交信息应简洁明了地描述代码改动内容
