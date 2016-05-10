<?php

namespace Schorsch3000\Pilight;


class PilightSwitch
{
    /** @var  Api */
    protected $api;
    /** @var  string */
    protected $name;

    protected $type;

    public function __construct(Api $api, $name, $type)
    {
        $this->api = $api;
        $this->api->checkSwitchNameValid($name);
        $this->name = $name;
        $this->type = $type;
    }


    /**
     * @param $state bool
     * @return bool
     */
    public function setState($state)
    {
        $state = (bool)$state;
        return $this->api->setSwitchState($this->name, $state, $this->type);
    }

    /**
     * @return bool
     */
    public function getState()
    {
        return $this->api->getSwitchState($this->name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

}