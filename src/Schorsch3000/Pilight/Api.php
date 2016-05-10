<?php

namespace Schorsch3000\Pilight;


class Api
{

    private static $ENDPOINT_CONFIG = 'config';
    private static $ENDPOINT_VALUES = 'values';
    private static $ENDPOINT_SEND = 'send';
    private $apiUrl;

    private $switchNames = [];

    public function getSwitches()
    {
        $this->loadSwitches();
        $switches = [];
        foreach ($this->switchNames as $name => $data) {
            $switches[$name] = new PilightSwitch($this, $name, $data['type']);
        }
        return $switches;
    }

    public function getSwitch($name)
    {
        $this->checkSwitchNameValid($name);
        return new PilightSwitch($this, $name,$this->switchNames[$name]['type']);

    }


    public function getSwitchState($name)
    {
        $this->loadSwitches();
        $this->checkSwitchNameValid($name);
        return $this->switchNames[$name];
    }

    public function setSwitchState($name, $state, $type)
    {
        $this->checkSwitchNameValid($name);
        $url = $this->getEndpointUrl(self::$ENDPOINT_SEND);
        $data = ["action" => "control", "code" => ["device" => $name, "state" => $this->boolToState($state, $type)]];
        $url .= '?' . json_encode($data);
        return file_get_contents($url);


    }

    public function setApiUrl($url)
    {
        $this->apiUrl = $url;
        $this->checkApiUrl();
    }

    private function checkApiUrl()
    {
        $configUrl = $this->getEndpointUrl(self::$ENDPOINT_CONFIG);
        $configJson = file_get_contents($configUrl);
        if (false === $configJson) {
            throw new ApiUrlInvalidException ("can't load content of $configUrl");
        }
        $config = json_decode($configJson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiUrlInvalidException ("Error parsing json: " . json_last_error_msg());
        }
        if (!isset($config->registry)) {
            throw new ApiUrlInvalidException("Endpoint delivers valid json but semms not to be a pilight configuration");
        }
    }

    private function getEndpointUrl($endpoint)
    {
        $uriParts = parse_url($this->apiUrl);
        if (!array_key_exists('path', $uriParts)) {
            $uriParts['path'] = '/' . $endpoint;
        } elseif (substr($uriParts['path'], -1) === '/') {
            $uriParts['path'] .= $endpoint;
        } else {
            $uriParts['path'] .= '/' . $endpoint;
        }
        return http_build_url($uriParts);
    }

    private function loadSwitches()
    {
        $valuesUrl = $this->getEndpointUrl(self::$ENDPOINT_VALUES);
        $valuesJson = file_get_contents($valuesUrl);
        if (false === $valuesJson) {
            throw new ApiUrlInvalidException ("can't load content of $valuesUrl");
        }
        $values = json_decode($valuesJson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiUrlInvalidException ("Error parsing json: " . json_last_error_msg());
        }
        $this->switchNames = [];
        foreach ($values as $value) {
            foreach ($value->devices as $name) {
                $this->switchNames[$name] = [
                    'state' => $this->stateToBool($value->values->state),
                    'type' => $value->type
                ];
            }
        }


    }

    private function stateToBool($state)
    {
        $data = ['on' => true, 'off' => false, 'running' => true, 'stopped' => false];
        return $data[$state];
    }

    private function boolToState($bool, $type = 1)
    {
        $data = [
            1 => [true => 'on', false => 'off'],
            7 => [true => 'running', false => 'stopped']
        ];

        return $data[$type][$bool];
    }

    public function checkSwitchNameValid($name)
    {
        if (array_key_exists($name, $this->switchNames)) {
            return true;
        }
        $this->loadSwitches();
        if (array_key_exists($name, $this->switchNames)) {
            return true;
        }
        throw new SwitchUnknownException($name.' is no known switch identifier');
    }


}