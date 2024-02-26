<?php

trait SANITIZE_PK
{
    public function sanitize_int_pk($value){
        $value_sanitized = filter_var($value, FILTER_SANITIZE_NUMBER_INT);

        return $value_sanitized;
    }

    public function sanitize_var_pk($value){
        $value_sanitized = htmlentities($value,ENT_QUOTES);

        return $value_sanitized;
    }
}
