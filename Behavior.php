<?php

namespace bhoft\yii2\autonumber;

use Exception;
use yii\db\BaseActiveRecord;
use yii\db\StaleObjectException;

/**
 * Behavior use to generate formated autonumber.
 * Use at ActiveRecord behavior
 *
 * ~~~
 * public function behavior()
 * {
 *     return [
 *         ...
 *         [
 *             'class' => 'bhoft\autonumber\Behavior',
 *             'targetClass' => 'app\models\MyModel',  // optional default OwnerClassname
 *             'group' => 'groupAttribute', // optional
 *             or
 *             'group' => array($this, 'getGroupId'),  // class function
 *             'format' => date('Ymd').'.?', // ? will replace with generated number
 *             'digit' => 6, // specify this if you need leading zero for number
 *
 *         ]
 *     ]
 * }
 * ~~~
 *
 * @author Original Author of mdm\yii2\autonumber Misbahul D Munir <misbahuldmunir@gmail.com>
 *         modified and extended by B Hoft <hoft@eurescom.eu>
 * @since 1.0
 */
class Behavior extends \yii\behaviors\AttributeBehavior
{
    /**
     * @var mixed the default format
     * default : '?'
     *
     * or a PHP callable that returns the default value which will
     * be assigned to the attributes being validated if they are empty.
     * The signature of the PHP callable should be as follows:
     * ```php
     * function foo($model, $attribute) {
     *     // compute value
     *     return $value;
     * }
     * ```
     * @see [[Behavior::$value]]
     */
    public $format;

    /**
     * targetClass class variable will owner classname will be used if not set
     *
     * @var string
     **/
    public $targetClass = null;

    /**
     * @var integer digit number of auto number
     */
    public $digit;

    /**
     * @var mixed Optional.
     */
    public $group;

    /**
     * @var boolean If set `true` number will genarate unique for owner classname.
     * Default `true`.
     */
    public $unique = true;

    /**
     * @var string
     */
    public $attribute;

    /**
     * [$db description]
     *
     * @var \yii\db\Connection or NULL
     */
    public $db;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!isset($this->targetClass)) {
            $this->targetClass = $this->unique ? get_class($this->owner) : false;
        }

        if ($this->attribute !== null) {
            $this->attributes[BaseActiveRecord::EVENT_BEFORE_INSERT][] = $this->attribute;
            $this->attributes[BaseActiveRecord::EVENT_BEFORE_UPDATE][] = $this->attribute;
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        // if autonumber already has a value return it
        $current_value = $this->owner->getAttribute($this->attribute);
        if ($current_value !==null) {
            return $current_value;
        }

        if (is_string($this->format) && method_exists($this->owner, $this->format)) {
            $format = call_user_func([$this->owner, $this->format], $event);
        } else {
            $format = is_callable($this->format) ? call_user_func($this->format, $event) : $this->format;
        }

        if (is_string($this->group) && method_exists($this->owner, $this->group)) {
            $groupValue = call_user_func([$this->owner, $this->group], $event);
        } elseif (is_string($this->group) && $this->owner instanceof BaseActiveRecord && $this->owner->getAttribute($this->group)) {
            $groupValue = $this->owner->getAttribute($this->group);
        } else {
            $groupValue = is_callable($this->group) ? call_user_func($this->group, $event) : $this->group;
        }


        $groupArray = [
            'class' => $this->targetClass,
            'groupBy' => $groupValue,
            'attribute' => $this->attribute,
            'format' => $format
        ];

        \Yii::debug('search for AutoNumber with md5 serialized array values '.print_r($groupArray, true), $category = 'yii2-autonumber');

        $group = md5(serialize($groupArray));

        \Yii::debug('search for AutoNumber with md5  '.$group, $category = 'yii2-autonumber');

        do {
            $repeat = false;
            try {

                //set AutoNumber db conn
                AutoNumber::setDbConn($this->db);

                $model = AutoNumber::findOne($group);
                if ($model) {
                    $number = $model->number + 1;
                } else {
                    $model = new AutoNumber([
                        'group' => $group,
                    ]);
                    $model->dbConn = $this->db;
                    $number        = 1;
                }
                $model->update_time = time();
                $model->number      = $number;
                $model->save(false);
            } catch (Exception $exc) {
                if ($exc instanceof StaleObjectException) {
                    $repeat = true;
                } else {
                    throw $exc;
                }
            }
        } while ($repeat);

        if ($format === null) {
            return $number;
        } else {
            return str_replace('?', $this->digit ? sprintf("%0{$this->digit}d", $number) : $number, $format);
        }
    }
}
