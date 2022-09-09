<?php namespace CnpDecoder;

class CNPDecoder extends DecoderEngine{
    /**
     * @var DecoderEngine
     */
    private static $object;

    /**
     * @param $cnp
     */
    public static function init($cnp)
    {
        self::$object = new DecoderEngine($cnp);
    }

    /**
     * @return int
     */
    public static function getBirthYear(){
        return self::$object->BirthYear();
    }

    /**
     * @return int
     */
    public static function getAge(){
        return self::$object->Age();
    }

    /**
     * @return false|int
     */
    public static function getBirthYearTimestamp(){
        return self::$object->BirthYearTimestamp();
    }

    /**
     * @return int
     */
    public static function getGender(){
        return self::$object->Gender();
    }

    /**
     * @return mixed
     */
    public static function getControlNumber(){
        return self::$object->ControlNumber();
    }

    /**
     * @return mixed
     */
    public static function getCountyCode(){
        return self::$object->CountyCode();
    }

    /**
     * @return mixed
     */
    public static function getCountyName(){
        return self::$object->CountyName();
    }

    /**
     * @return string
     */
    public static function getDaysLeftUntilBirthday(){
        return self::$object->DaysLeftUntilBirthday();
    }
}