<?php

namespace bhoft\yii2\autonumber;

use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * Validator use to fill autonumber
 *
 * Use to fill attribute with formatet autonumber.
 *
 * Usage at [[$owner]] rules()
 *
 *
 * ~~~
 * use bhoft\autonumber\AutonumberValidator;
 *
 * return [
 *     [['sales_num'], 'autonumber', 'format'=>'SA.'.date('Ymd').'?'],
 *
 *     [['submission_num'], AutonumberValidator::className(), 'format'=>'?', 'targetClass' => 'app\models\MyModel', 'group' => 'call_id'],
 *
 *     ...
 * ]
 * ~~~
 *
 * @author Original Author of mdm\yii2\autonumber Misbahul D Munir <misbahuldmunir@gmail.com>
 *         modified and extended by B Hoft <hoft@eurescom.eu>
 * @since 1.0
 */
class AutonumberValidator extends \yii\validators\Validator
{
    /**
     * [$db description]
     *
     * @var \yii\db\Connection or NULL
     */
    public $db;

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
    public $format = '?';

    /**
     * targetClass class variable will event object classname will be used if not set
     *
     * @var string
     **/
    public $targetClass = null;

    /**
     * @var integer digit number of auto number
     */
    public $digit;

    /**
     * @var mixed
     */
    public $group;

    /**
     * @var boolean
     */
    public $unique = true;

    /**
     * @inheritdoc
     */
    public $skipOnEmpty = false;

    /**
     * @var boolean
     */
    public $throwIsStale = false;

    /**
     * @var array
     */
    private static $_executed = [];

    /**
     * @inheritdoc
     */
    public function validateAttribute($object, $attribute)
    {
        if (!isset($this->targetClass)) {
            $this->targetClass = $this->unique ? get_class($object) : false;
        }

        if ($this->isEmpty($object->$attribute)) {
            $eventId = uniqid();
            $object->on(ActiveRecord::EVENT_BEFORE_INSERT, [$this, 'beforeSave'], [$eventId, $attribute]);
            $object->on(ActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'beforeSave'], [$eventId, $attribute]);
        }
    }

    /**
     * Handle for [[\yii\db\ActiveRecord::EVENT_BEFORE_INSERT]] and [[\yii\db\ActiveRecord::EVENT_BEFORE_UPDATE]]
     * @param \yii\base\ModelEvent $event
     */
    public function beforeSave($event)
    {
        list($id, $attribute) = $event->data;
        if (isset(self::$_executed[$id])) {
            return;
        }

        /* @var $object \yii\db\ActiveRecord */
        $object = $event->sender;
        if (is_string($this->format) && method_exists($object, $this->format)) {
            $format = call_user_func([$object, $this->format], $object, $attribute);
        } else {
            $format = is_callable($this->format) ? call_user_func($this->format, $object, $attribute) : $this->format;
        }

        if (is_string($this->group) && method_exists($object, $this->group)) {
            $groupValue = call_user_func([$object, $this->group], $event);
        } elseif (is_string($this->group) && $object instanceof ActiveRecord && $object->getAttribute($this->group)) {
            $groupValue = $object->getAttribute($this->group);
        } else {
            $groupValue = is_callable($this->group) ? call_user_func($this->group, $object, $attribute) : $this->group;
        }

        $groupArray = [
            'class'     => $this->targetClass,
            'groupBy'     => $groupValue,
            'attribute' => $attribute,
            'format'    => $format,
        ];
        \Yii::debug('search for AutoNumber with md5 serialized array values ' . print_r($groupArray, true), $category = 'yii2-autonumber');

        $group = md5(serialize($groupArray));

        \Yii::debug('search for AutoNumber with md5  ' . $group, $category = 'yii2-autonumber');

        AutoNumber::setDbConn($this->db);

        $model = AutoNumber::findOne($group);
        if ($model) {
            $number = $model->number + 1;
        } else {
            $model = new AutoNumber([
                'group' => $group,
            ]);
            $model->dbConn = $this->db;

            $number = 1;
        }
        $model->update_time = time();
        $model->number      = $number;

        if ($format === null) {
            $object->$attribute = $number;
        } else {
            $object->$attribute = str_replace('?', $this->digit ? sprintf("%0{$this->digit}d", $number) : $number, $format);
        }


        self::$_executed[$id] = true;
        try {
            $model->save(false);
        } catch (\Exception $exc) {
            $event->isValid = false;
            if ($this->throwIsStale || !($exc instanceof StaleObjectException)) {
                throw $exc;
            }
        }
    }
}
