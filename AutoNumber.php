<?php

namespace bhoft\yii2\autonumber;

/**
 * This is the model class for table "auto_number".
 *
 * @property string $group
 * @property string $template
 * @property integer $number
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AutoNumber extends \yii\db\ActiveRecord
{

    private static $dbConn;

    /**
     * set dynamic db connection
     *
     * @param [type] $db \yii\db\Connection
     */
    public static function setDbConn($db = null)
    {
        self::$dbConn = $db;
    }

    /**
     * getDb
     *
     * @return [type] [description]
     */
    public static function getDb()
    {
        if (self::$dbConn === null) {
            self::$dbConn = \Yii::$app->db;
        }
        return self::$dbConn;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auto_number}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['optimistic_lock', 'number'], 'default', 'value' => 1],
            [['group'], 'required'],
            [['number'], 'integer'],
            [['group'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'template' => 'Template Num',
            'number'   => 'Number',
        ];
    }

    /**
     * @inheritdoc
     */
    public function optimisticLock()
    {
        return 'optimistic_lock';
    }
}
