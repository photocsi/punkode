<?php

namespace Punkode;

/*le funzioni di input tipo $this->option_pk() non sono collegate direttaemnte alla classe, ma inserite in classi che estendono input allora funzionano lo stesso*/

trait TOOL_PK
{
/* serve per settare il type column in un valore utilizzabile in punkode ad esempio varchar diventa var */
    static function set_column_type_pk($value_column_type)
    {
        switch ($value_column_type) {
            case str_starts_with($value_column_type, 'varchar' ) === true:
                return 'var';
                break;

            case str_starts_with($value_column_type, 'int') === true:
                return 'int';
                break;

            case str_starts_with($value_column_type, 'text') === true:
                return 'text';
                break;

            case str_starts_with($value_column_type, 'date') === true:
                return 'dat';
                break;

            case str_starts_with($value_column_type, 'tinyint') === true:
                return 'tinyi';
                break;

            case str_starts_with($value_column_type, 'tinytext') === true:
                return 'tinyt';
                break;

            case str_starts_with($value_column_type, 'char') === true:
                return 'cha';
                break;

            case str_starts_with($value_column_type, 'longtext') === true:
                return 'lon';
                break;

            case str_starts_with($value_column_type, 'json') === true:
                return 'json';
                break;

            case str_starts_with($value_column_type, 'datetime') !== false:
                return 'datt';
                break;

            case str_starts_with($value_column_type, 'timestamp') !== false:
                return 'tims';
                break;

            case str_starts_with($value_column_type, 'float') !== false:
                return 'flo';
                break;

            case str_starts_with($value_column_type, 'double') !== false:
                return 'dou';
                break;

            case str_starts_with($value_column_type, 'time') !== false:
                return 'tim';
                break;

            case str_starts_with($value_column_type, 'smallint') !== false:
                return 'sma';
                break;

            case str_starts_with($value_column_type, 'mediumint') !== false:
                return 'medi';
                break;

            case str_starts_with($value_column_type, 'bigint') !== false:
                return 'big';
                break;

            case str_starts_with($value_column_type, 'decimal') !== false:
                return 'dec';
                break;

            case str_starts_with($value_column_type, 'real' ) !== false:
                return 'rea';
                break;

            case str_starts_with($value_column_type, 'bit') !== false:
                return 'bit';
                break;

            case str_starts_with($value_column_type, 'boolean') !== false:
                return 'boo';
                break;

            case str_starts_with($value_column_type, 'serial') !== false:
                return 'ser';
                break;


            case str_starts_with($value_column_type, 'mediumtext') !== false:
                return 'medt';
                break;

            case str_starts_with($value_column_type, 'binary') !== false:
                return 'bin';
                break;
            case str_starts_with($value_column_type, 'varbinary') !== false:
                return 'varb';
                break;
            case str_starts_with($value_column_type, 'tinyblob') !== false:
                return 'tinyb';
                break;
            case str_starts_with($value_column_type, 'blob') !== false:
                return 'blo';
                break;
            case str_starts_with($value_column_type, 'mediumblob') !== false:
                return 'medb';
                break;
            case str_starts_with($value_column_type, 'longblob') !== false:
                return 'lonb';
                break;
            case str_starts_with($value_column_type, 'enum') !== false:
                return 'enu';
                break;
            case str_starts_with($value_column_type, 'set') !== false:
                return 'set';
                break;
            case str_starts_with($value_column_type, 'geometry') !== false:
                return 'geo';
                break;
            case str_starts_with($value_column_type, 'point') !== false:
                return 'poi';
                break;
            case str_starts_with($value_column_type, 'linestring') !== false:
                return 'lin';
                break;
            case str_starts_with($value_column_type, 'polygon') !== false:
                return 'pol';
                break;
            
            case str_starts_with($value_column_type, 'multipoint') !== false:
                return 'mul';
                break;
            case str_starts_with($value_column_type, 'multilinestring') !== false:
                return 'muls';
                break;
            case str_starts_with($value_column_type, 'multipolygon') !== false:
                return 'mulp';
                break;
            case str_starts_with($value_column_type, 'geometricollection') !== false:
                return 'geoc';
                break;

            case str_starts_with($value_column_type, 'year') !== false:
                return 'yea';
                break;

            default:
                return '';
                break;
        }
    }

    
}
