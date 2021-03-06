<?php

namespace SoftEtherApi\Containers
{
    require('SoftEtherValueType.php');
    use Exception;

    class SoftEtherProtocol
    {
        public static function SerializeInt($val)
        {
            return pack('N', $val);
        }

        public static function SerializeLong($val)
        {
            return pack('J', $val);
        }

        private static function SerializeBytes(&$val)
        {
            return pack('C*', ...$val);
        }

        public static function DeserializeInt(&$val)
        {
            return unpack('N', $val)[1];
        }

        public static function Serialize($model)
        {
            $returnVal = '';
            $returnVal .= self::SerializeInt(count($model));

            foreach ($model as $key => $val)
            {
                $keyLen = strlen($key);

                $returnVal .= self::SerializeInt($keyLen + 1);
                $returnVal .= $key;

                $returnVal .= self::SerializeInt($val['type']);
                $returnVal .= self::SerializeInt(count($val['value']));

                switch ($val['type'])
                {
                    case SoftEtherValueType::Int:
                    {

                        foreach ($val['value'] as $t)
                            $returnVal .= self::SerializeInt($t);
                        break;
                    }
                    case SoftEtherValueType::Raw:
                    {
                        foreach ($val['value'] as $t)
                        {
                            $returnVal .= self::SerializeInt(count($t));
                            $returnVal .= self::SerializeBytes($t);
                        }
                        break;
                    }
                    case SoftEtherValueType::String:
                    {
                        foreach ($val['value'] as $t)
                        {
                            $returnVal .= self::SerializeInt(strlen($t));
                            $returnVal .= $t;
                        }
                        break;
                    }
                    case SoftEtherValueType::UnicodeString:
                    {
                        foreach ($val['value'] as $t)
                        {
                            $returnVal .= self::SerializeInt(strlen($t));
                            $returnVal .= $t;
                        }
                        break;
                    }
                    case SoftEtherValueType::Int64:
                    {
                        foreach ($val['value'] as $t)
                            $returnVal .= self::SerializeLong($t);
                        break;
                    }
                    default:
                        throw new Exception("ValueType is not valid");
                }
            }

            return $returnVal;
        }

        private static function readInt(&$val, &$index)
        {
            $retValArr = unpack('N', $val, $index);
            $index += 4;
            return (int)$retValArr[1];
        }

        private static function readLong(&$val, &$index)
        {
            $retValArr = unpack('J', $val, $index);
            $index += 8;
            return (int)$retValArr[1];
        }

        private static function readString(&$val, &$index, $size)
        {
            $retVal = substr($val, $index, $size);
            $index += $size;
            return $retVal;
        }

        private static function readBytes(&$val, &$index, $size)
        {
            $retVal = unpack('C*', substr($val, $index, $size));
            $index += $size;
            return $retVal;
        }

        public static function Deserialize(&$val)
        {
            $index = 0;
            $count = self::readInt($val, $index);

            $res = [];
            for ($i = 0; $i < $count; $i++)
            {
                $keyLen = self::readInt($val, $index);
                $key = self::readString($val, $index, $keyLen - 1);
                $valueType = self::readInt($val, $index);
                $valueCount =  self::readInt($val, $index);

                $list = [];
                for ($j = 0; $j < $valueCount; $j++)
                {
                    switch ($valueType)
                    {
                        case SoftEtherValueType::Int:
                        {
                            array_push($list, self::readInt($val, $index));
                            break;
                        }
                        case SoftEtherValueType::Raw:
                        {
                            $strLen = self::readInt($val, $index);
                            array_push($list, self::readBytes($val, $index, $strLen));
                            break;
                        }
                        case SoftEtherValueType::String:
                        {
                            $strLen = self::readInt($val, $index);
                            array_push($list, self::readString($val, $index, $strLen));
                            break;
                        }
                        case SoftEtherValueType::UnicodeString:
                        {
                            $strLen = self::readInt($val, $index);
                            //softether adds a additional 0-byte to every string. For future sake, just trim it instead of reading a byte less
                            array_push($list, rtrim(self::readString($val, $index, $strLen), "\0"));
                            break;
                        }
                        case SoftEtherValueType::Int64:
                        {
                            array_push($list, self::readLong($val, $index));
                            break;
                        }
                        default:
                            throw new Exception("ValueType is not valid");
                    }
                }

                $res[$key] = ['type' => $valueType, 'value' => $list];
            }
            return $res;
        }
    }
}