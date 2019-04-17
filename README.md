Auto Number Extension for Yii 2
===============================

Yii2 extension to genarete formated autonumber. It can be used for generate
document number.

This extension forked from [mdm/yii2-autonumber](https://github.com/mdmsoft/yii2-autonumber) and extended with some modifications.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist bhoft/yii2-autonumber "~1.0"
```

or add

```
"bhoft/yii2-autonumber": "~1.0"
```

to the require section of your `composer.json` file.


Usage
-----

Prepare required table by execute yii migrate.

```
yii migrate --migrationPath=@bhoft/yii2/autonumber/migrations
```

if wantn't use db migration. you can create required table manually.

```sql
CREATE TABLE auto_number (
    "group" varchar(32) NOT NULL,
    "number" int,
    optimistic_lock int,
    update_time int,
    PRIMARY KEY ("group")
);
```

Once the extension is installed, simply modify your ActiveRecord class:

```php
public function behaviors()
{
	return [
		[
			'class' => 'bhoft\yii2\autonumber\Behavior',
			'targetClass' => 'app\models\MyModel',  // optional default OwnerClassname
			'attribute' => 'sales_num', // required
			'group' => 'groupAttribute', // optional
				// or as class function
	            // 'group' => array($this, 'getGroupId'),  // 
	            // or
	            // 'group' => $this->groupId'),  // 
			'format' => 'SA.'.date('Y-m-d').'.?' , // format auto number. '?' will be replaced with generated number
				//you could also use " 'format' => function($event){ return 'SA.'.date('Y-m-d').'.?' } "
			'digit' => 4 // optional, default to null.
			//'db' => Yii::app()->db,  // optional
		],
	];
}

// it will set value $model->sales_num as 'SA.2014-06-25.0001'
```

Instead of behavior, you can use this extension as validator

```php

use bhoft\autonumber\AutonumberValidator;
...
public function rules()
{
    return [
        [['sales_num'], AutonumberValidator::className(), 'format'=>'SA.'.date('Y-m-d').'.?'],
        ...
        [['submission_num'], AutonumberValidator::className(), 'format'=>'?', 'targetClass' => 'app\models\MyModel', 'group' => 'call_id'],
    ];
}
```

- [Api Documentation of original version](http://mdmsoft.github.io/yii2-autonumber/index.html)
