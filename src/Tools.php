<?php

namespace Sentrasoft\Netutils;

use Sentrasoft\Netutils\Ping;
use Sentrasoft\Netutils\Network;
use Sentrasoft\Netutils\IP;
use Sentrasoft\Netutils\Range;
use Sentrasoft\Netutils\WakeOnLan;

class Tools
{
    /**
     * @var IP
     */
    private $ip;
    /**
     * @var IP
     */
    private $netmask;

    /**
     * Create a new Skeleton Instance
     */
    public function __construct($ip = '127.0.0.1', $netmask='255.255.255.0')
    {
        $this->setIP($ip);
        $this->setNetmask($netmask);
    }

    public function setIP($ip)
    {
        $this->ip = $ip;
    }

    public function getIP()
    {
        return $this->ip;
    }

    public function setNetmask($netmask)
    {
        $this->netmask = $netmask;
    }

    public function getNetmask()
    {
        return $this->netmask;
    }

    /**
     * @param IP $ip
     * @param IP $netmask
     */
    public function ping($ip = null, $ttl = 255)
    {
        if(is_null($ip)){
            return new Ping($this->ip, $ttl = 255);
        } else {
            return new Ping($ip, $ttl = 255);
        }
    }

    /**
     * @param IP $ip
     * @param IP $netmask
     */
    public function network($ip=null, $netmask=null)
    {
        if (is_null($ip)) {
            $ip = $this->ip;
        }
        if (is_null($netmask)){
            $netmask = $this->netmask;
        }
        return new Network($ip, $netmask);
    }

    /**
     * @param string ip
     * @throws \Exception
     */
    public function ip($ip=null)
    {
        if (is_null($ip)) {
            $ip = $this->ip;
        }
        return new IP($ip);
    }

    /**
     * @param IP $firstIP
     * @param IP $lastIP
     * @throws \Exception
     */
    public function range($firstIP, $lastIP)
    {
        return new Range($firstIP, $lastIP);
    }

    public function WakeOnLan($mac = null,$addr = "255.255.255.255")
    {
        return new WakeOnLan($mac,$addr);
    }
}
