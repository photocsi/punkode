<?php

namespace Punkode;

trait SAFE_PK
{
    public function sanitize_int_pk($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_SANITIZE_NUMBER_INT);

        return substr($value_sanitized,0,11);
    }

    public function validate_int_pk($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_VALIDATE_INT);
       

        return substr($value_sanitized,0,11);
    }

    public function sanitize_var_pk($value)
    {
        $value_sanitized = htmlentities(trim($value), ENT_QUOTES);

        return substr($value_sanitized,0,80);
    }

    public function validate_var_pk($value)
    {
        $value_decode = html_entity_decode((string) $value);
        $value_sanitized = htmlspecialchars($value_decode);

        return substr($value_sanitized,0,80);
    }

    public function sanitize_cha_pk($value)
    {
        $value_sanitized = htmlentities(trim($value), ENT_QUOTES);

        return $value_sanitized;
    }

    public function validate_cha_pk($value)
    {
        $value_decode = html_entity_decode((string) $value);
        $value_sanitized = htmlspecialchars($value_decode);

        return $value_sanitized;
    }
    public function sanitize_date_pk($value)
    {
        $value_sanitized = htmlentities($value, ENT_QUOTES);

        return substr($value_sanitized,0,12);
    }

    public function validate_date_pk($value)
    {
        $value_control = preg_replace("([^0-9/] | [^0-9-])", "", htmlentities($value));
        if ($value_control != '0000-00-00') {
            $date = date_create($value_control);
            $value_sanitized = date_format($date, 'd/M/y');
        } else {
            $value_sanitized = '';
        }

        return substr($value_sanitized,0,12);
    }

    public function sanitize_email_pk($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_SANITIZE_EMAIL);

        return substr($value_sanitized,0,40);
    }

    public function validate_email_pk($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_VALIDATE_EMAIL);

        return substr($value_sanitized,0,40);
    }

    public function sanitize_pass_pk($value)
    {
        $value_sanitized = password_hash(trim($value), PASSWORD_ARGON2I);

        return substr($value_sanitized,0,255);
    }
    public function validate_pass_pk($value)
    {
        $value_validate = "********";

        return $value_validate;
    }

    public function sanitize_float_pk($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_SANITIZE_NUMBER_FLOAT);

        return substr($value_sanitized,0,18);
    }

    public function validate_float_pk($value)
    {
        $value_sanitized = filter_var($value, FILTER_VALIDATE_FLOAT);

        return substr($value_sanitized,0,18);
    }

    public function validate_bool_pk($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $value_sanitized;
    }

    public function sanitizie_only_char($value)
    {
        trim($value);
        $regex = '/^[a-z _ A-Z]+$/';
        if (preg_match($regex,$value)) {
            $value_sanitized = str_replace(' ','_', $value);
        } else {
            $value_sanitized = '';
        }
        return $value_sanitized;
    }
}
