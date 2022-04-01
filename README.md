#### 数据基本操作

微擎系统数据库操作使用 PDO 兼容方式，以参数绑定的形式进行查询操作。
系统已对 PDO 兼容性进行检测及封装。下面具体说明一下数据库的几种基本操作方法。

#### 范围条件操作

在微擎20160601以后的版本中，增加了pdo_get、pdo_getall、pdo_getcolumn、pdo_getslice、pdo_insert、pdo_update、pdo_delete的范围条件的支持，具体支持的范围操作符如下：

```
array('>', '<', '<>', '!=', '>=', '<=', 'NOT IN', 'not in', '+=', '-=');
```

> 字段名与操作符组成条件数组的键名，字段名与操作符中间间隔一个空格，具体使用方法如下：

```
//获取acid大于269的公众号
$account = pdo_get('account', array('acid >' => '269'));

//增加一次用户的错误登录次数，两次变为2即可
pdo_update('users_failed_login', array('count +=' => 1), array('username' => 'mizhou'));
```

#### 查询

查询是数据库操作中使用最频繁的操作，微擎系统封装了一些函数适用于不同的场景，以下逐个说明

##### pdo_get

根据条件（AND连接）到指定的表中获取一条记录

- $tablename 参数指定要查询的数据表名，此处传入的表名不要使用tablename()函数
- $condition 参数指定查询的条件，以是 AND 连接，支持大于，小于等范围条件。*具体使用查看本章节第二段范围条件操作*
- $fields 参数指定查询返回的字段列表
- $limit 参数指定查询表中获取一条记录

```
array | boolean pdo_get($tablename, $condition = array(), $fields = array());
```

示例:

```
//根据uid获取用户的用户名和用户Id信息
//生成的SQL等同于：SELECT username, uid FROM ims_users WHERE uid = '1' LIMIT 1
$user = pdo_get('users', array('uid' => 1), array('username', 'uid'));
 
//生成的SQL等同于：SELECT username FROM ims_users WHERE username = 'mizhou' AND status = '1' LIMIT 1
$user = pdo_get('users', array('username' => 'mizhou', 'status' => 1), array('username'));
```

##### pdo_getcolumn

根据条件（AND连接）到指定的表中获取一条记录的指定字段

- $tablename 参数指定要查询的数据表名，此处传入的表名不要使用tablename()函数
- $condition 参数指定查询的条件，以是 AND 连接，支持大于，小于等范围条件.。*具体使用查看本章节第二段范围条件操作*
- $field 参数指定查询返回的字段
- $limit 参数指定查询表中获取一条记录

```
string | int pdo_getcolumn($tablename, $condition = array(), $field, $limit=1);
```

示例:

```
//根据uid获取用户的用户名
//生成的SQL等同于：SELECT username FROM ims_users WHERE uid = '1' LIMIT 1
$username = pdo_getcolumn('users', array('uid' => 1), 'username',1);
```

##### pdo_getall

根据条件（AND连接）到指定的表中获取全部记录

- $condition 参数指定查询的条件，以是 AND 连接，支持大于，小于等范围条件.。*具体使用查看本章节第二段范围条件操作*
- $keyfield 参数传入一个已存在的字段名称，结果数组键值就为该字段，否则为自然排序
- $orderby 参数指定查询结果按哪个字段排序
- $limit 参数指定查询语句的LIMIT值，array(start, end) 或是直接传入范围 2,3
- 其它参数同pdo_get函数

```
array | boolean pdo_getall($tablename, $condition = array(), $fields = array(), $keyfield = '',$orderby = array(), $limit = array()) {
```

示例:

```
//获取全部启用的用户
//生成的SQL等同于：SELECT * FROM ims_users WHERE status = '1'
$user = pdo_getall('users', array('status' => 1));
//获取从第一条数据开始的10条启用的用户
//生成的SQL等同于：SELECT * FROM ims_users WHERE status =' 2' ORDER BY uid,groupid LIMIT 0, 10
$user = pdo_getall('users', array('status' => 1), array() , '' , array('uid','groupid') , array(1,10));

$user1 = pdo_getall('users', array('status' => 1), array() , '' , 'uid DESC' , array(1,10));
```

##### pdo_getslice

根据条件（AND连接）到指定的表中获取某个区间的记录，此函数和 pdo_getall 的区别是可以指定limit 值

- $condition 参数指定查询的条件，以是 AND 连接，支持大于，小于等范围条件.。*具体使用查看本章节第二段范围条件操作*
- $limit 参数指定查询语句的LIMIT值，array(start, end) 或是直接传入范围 2,3
- $total 参数指定查询结果的总条数，方便进行分页操作
- $orderby 参数指定查询结果按哪个字段排序

```
array | boolean pdo_getslice($tablename, $condition = array(), $limit = array(), &$total = null, $fields = array(), $keyfield = '', $orderby = array())
```

示例：

```
//获取从第一条数据开始的10条启用的用户
//生成的SQL等同于$user = SELECT * FROM  ims_users WHERE status ='2' ORDER BY uid,groupid LIMIT 0, 10
$user = pdo_getslice('users', array('status' => 2), array(1,10) , $total , array() , '' , array('uid','groupid'));
```

\#####pdo_fetch
根据SQL语句，查询一条记录

- $sql 参数指定要返回记录集的SQL语句
- $params 参数指定为SQL语句中的参数绑定传值，防止SQL注入
  需要注意的是使用参数绑定时，SQL语中等号后不需要使用引号，传入的值必须与绑定的名称一致

```
array | boolean pdo_fetch($sql, $params = array());
```

示例：

```
// :uid 是参数的一个占位符，没有使用引号，传入的第二个参数中要与SQL中的占位名称相同
$user = pdo_fetch("SELECT username, uid FROM ".tablename('users')." WHERE uid = :uid LIMIT 1", array(':uid' => 1));
// LIKE 占位的使用方法
$user = pdo_fetch("SELECT * FROM ".tablename('users')." WHERE username LIKE :username", array(':username' => '%mizhou%'));
```

##### pdo_fetchcolumn

根据SQL语句，查询第一条记录的第N列的值，此语句与 pdo_fetch 使用相同，只是此函数返回的不是一个数组而是一个字符串

- $column 参数指定返回记录集的第几列数据

```
string | boolean pdo_fetchcolumn($sql, $params = array(), $column = 0)
```

示例：

```
// 获取用户的总数，返回的值是一个数字 
$user_total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('users'));
```

##### pdo_fetchall

根据SQL语句，查询全部记录，使用方法与pdo_fetch相同

```
array | boolean pdo_fetchall($sql, $params = array(), $keyfield = '')
```

示例：

```
// 需要注意的是，返回的数组的键值为用户的uid
$user = pdo_fetchall("SELECT username, uid FROM ".tablename('users'), array(), 'uid');
```

\####变更
以下说明插入，更新，删除操作的几个函数。

##### pdo_insert

对指定数据表插入一条新记录

- $tablename 参数指定要插入记录的数据表名，此处传入的表名不要使用tablename()函数
- $data 参数指定要插入的记录，格式为与数据表字段对应的关联数组
- $replace 参数指定插入方式使用 INSERT 语句或是 REPLACE 语句(查找到主键相同的数据选择update)

```
int | boolean pdo_insert($tablename, $data = array(), $replace = false)
```

示例：

```
//添加一条用户记录，并判断是否成功
$user_data = array(
	'username' => 'mizhou1',
	'status' => '1',
);
$result = pdo_insert('users', $user_data);
if (!empty($result)) {
	$uid = pdo_insertid();
	message('添加用户成功，UID为' . $uid);
}
```

##### **pdo_update**

更新指定的数据表的记录

- $glue 参数指定前面 $condition 数组条件的关联字 AND 或是 OR

```
array | boolean pdo_update($tablename, $data = array(), $condition, $glue = 'AND')
```

示例：

```
//更uid等于2的用户的用户名
$user_data = array(
	'username' => 'mizhou2',
);
$result = pdo_update('users', $user_data, array('id' => 2));
if (!empty($result)) {
	message('更新成功');
}
```

##### pdo_delete

删除指定条件的数据

```
int | boolean pdo_delete($tablename, $condition = array(), $glue = 'AND')
```

示例：

```
//删除用户名为mizhou2的记录
$result = pdo_delete('users', array('username' => 'mizhou2'));
if (!empty($result)) {
	message('删除成功');
}
```

\####运行SQL

当更新，插入，删除无法满足时，可以直接构造SQL语句进行操作

##### pdo_query

运行一条SQL语句

- $params 指定SQL语句中绑定参数的值，参数占位与 pdo_fetch 一致

```
int | boolean query($sql, $params = array())
```

示例：

```
//更uid等于2的用户的用户名
$result = pdo_query("UPDATE ".tablename('users')." SET username = :username, age = :age WHERE uid = :uid", array(':username' => 'mizhou2', ':age' => 18, ':uid' => 2));
 
//删除用户名为mizhou2的记录
$result = pdo_query("DELETE FROM ".tablename('users')." WHERE uid = :uid", array(':uid' => 2));
if (!empty($result)) {
	message('删除成功');
}
```

\#####**pdo_run**
批量执行SQL语句

- $stuff 函数将会将此参数指定的值，替换为当前系统的表前缀。
  注：与pdo_query不同的是，pdo_run是可以一次执行多条SQL语句，每条SQL必须以;分隔。

```
boolean run($sql, $stuff = 'ims_')
```

示例：

```
$sql = <<<EOF
CREATE TABLE IF NOT EXISTS `ims_multisearch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `weid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
 
CREATE TABLE IF NOT EXISTS `ims_multisearch_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reid` int(10) unsigned NOT NULL,
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reid` (`reid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
EOF;
 
pdo_run($sql);
```

#### 辅助函数

##### pdo_fieldexists

检查表中是否存在某个字段

- $tablename 参数指定要检查的表名称
- $fieldname 参数指定要检查是否存在的字段名

```
boolean pdo_fieldexists($tablename, $fieldname)
```

示例：

```
//如果shopping_goods表中不存在credit字段，则新增credit字段
if(!pdo_fieldexists('shopping_goods', 'credit')) {
	pdo_query("ALTER TABLE ".tablename('shopping_goods')." ADD `credit` int(11) NOT NULL DEFAULT '0';");
}
```

**pdo_indexexists**
检查表中是否存在某个索引

- $tablename 参数指定要检查的表名称
- $indexname 参数指定要检查是否存在的索引名

```
boolean pdo_indexexists($tablename, $indexname)
```

示例：

```
//如果site_slide表中不存在multiid索引，则新增multiid索引
if (!pdo_indexexists('site_slide', 'multiid')) {
	pdo_query("ALTER TABLE ".tablename('site_slide')." ADD INDEX `multiid` (`multiid`);");
}
```

##### pdo_tableexists

检查数据库中是否存在某个表

```
boolean pdo_tableexists($tablename)
```

##### pdo_logging

调试运行SQL语句，显示执行过的SQL的栈情况

```
pdo_logging();
 
//调用该函数结果如下
Array
(
[0] => Array
	(
		[sql] => SET NAMES 'utf8';
		[error] => Array
			(
				[0] => 00000
				[1] => 
				[2] => 
			)
	)
[1] => Array
	(
		[sql] => SELECT `value` FROM `ims_core_cache` WHERE `key`=:key
		[params] => Array
			(
				[:key] => setting
			)
		[error] => Array
			(
				[0] => 00000
				[1] => 
				[2] => 
			)
	)
)
```
