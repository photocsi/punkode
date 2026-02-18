<?php

namespace Punkode;

trait HOOK_PK
{

    public $name = '';
    public $callback = '';

    public function hook($name, $callback){
        $this->name = $name;
        $this->callback = $callback;
        $par=$this->name.'popopo';
        $hook_client=$callback($par);
    return $hook_client;
    }

}