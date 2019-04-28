# yafa
基于yaf框架的二次封装

## 构建须知

* php版本 >= 7.1
* 安装PDO扩展
* 安装Memcached扩展
* 安装Redis扩展
* 安装Yaf扩展

## 此系统基于php yaf扩展框架构建

* yaf介绍:http://www.laruence.com/2011/05/12/2009.html
* yaf版本:请用3.0.7版本编译构建
* yaf地址:http://pecl.php.net/package/yaf/3.0.7
* yaf文档:http://php.net/manual/en/book.yaf.php
* yaf用户手册:http://www.laruence.com/manual/

## !!!必须配置 开发环境php.ini yaf配置

```code
[yaf]
yaf.environ = 'dev'
yaf.use_namespace = 1
yaf.use_spl_autoload = 1
```

## 一些常用的文档

* [Particle\Validator](http://validator.particle-php.com/en/latest/rules/#included-validation-rules) 是一个小巧优雅的验证类库，提供了一个非常简洁的API

## 开发的一些原则和规范

 * 编写的代码请遵循 PSR-2 风格 [关于PSR](https://psr.phphub.org/)
 * 所有代码请设置为utf-8编码
 * 数据库所有编码请设置为utf8mb4
 * 在API层请调用SDK的Service相关方法访问数据
 * 在API层禁止直接调用SDK的Model相关方法

## 缓存key规范和原则

 * Memcache相关的key前缀以Model类名为开头,这样可以防止Key重复
 * Redis的key以数据类型或者使用类型开头,例如字符串STR:xxx 列表/链表LIST: 队列MQ:等

