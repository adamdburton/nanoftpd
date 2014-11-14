<?php

namespace AdamDBurton\NanoFTPd\NanoFTPd;

class Pool
{
    protected $pool;

    function __construct()
    {
        $this->pool = array();
    }

    function add($value)
    {
        if(!in_array($value, $this->pool))
        {
            return array_push($this->pool, $value);
        }
        else
        {
            return 2;
        }
    }

    function remove($rem_value)
    {
        if(in_array($rem_value, $this->pool))
        {
            $new_pool = array();

            foreach($this->pool as $value)
            {
                if ($value == $rem_value) continue;
                $new_pool[] = $value;
            }

            $this->pool = $new_pool;

            return true;
        }
        else
        {
            return false;
        }
    }

    function exists($value)
    {
        return in_array($value, $this->pool);
    }
}