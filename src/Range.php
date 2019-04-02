<?php
namespace Sentrasoft\Netutils;

use Sentrasoft\Netutils\IP;

class Range implements \Iterator, \Countable
{
	use PropertyTrait;

	/**
	 * @var IP
	 */
	private $firstIP;
	/**
	 * @var IP
	 */
	private $lastIP;
	/**
	 * @var int
	 */
	private $position = 0;

	/**
	 * @param IP $firstIP
	 * @param IP $lastIP
	 * @throws \Exception
	 */
	public function __construct($firstIP, $lastIP)
	{
		$this->setFirstIP(new IP($firstIP));
		$this->setLastIP(new IP($lastIP));
	}

	/**
	 * @param string $data
	 * @return Range
	 */
	public static function parse($data)
	{
		if (strpos($data,'/') || strpos($data,' ')) {
			$network = Network::parse($data);
			$firstIP = $network->getFirstIP();
			$lastIP  = $network->getLastIP();
		} elseif (strpos($data, '*')) {
			$firstIP = IP::parse(str_replace('*', '0', $data));
			$lastIP  = IP::parse(str_replace('*', '255', $data));
		} elseif (strpos($data, '-')) {
			list($first, $last) = explode('-', $data, 2);
			$firstIP = IP::parse($first);
			$lastIP  = IP::parse($last);
		} else {
            $firstIP = IP::parse($data);
            $lastIP = clone $firstIP;
        }

		return new self($firstIP, $lastIP);
	}

	/**
	 * @param IP|Network|Range $find
	 * @return bool
	 * @throws \Exception
	 */
	public function contains($find)
	{
		if($find instanceof IP) {
			$within = ($find->inAddr() >= $this->firstIP->inAddr())
				&& ($find->inAddr() <= $this->lastIP->inAddr());
		} elseif($find instanceof Range || $find instanceof Network) {
			/**
			 * @var Network|Range $find
			 */
			$within = ($find->getFirstIP()->inAddr() >= $this->firstIP->inAddr())
				&& ($find->getLastIP()->inAddr() <= $this->lastIP->inAddr());
		} else {
			throw new \Exception('Invalid type');
		}

		return $within;
	}

	/**
	 * @param IP $ip
	 * @throws \Exception
	 */
	public function setFirstIP(IP $ip)
	{
		if($this->lastIP && $ip->inAddr() > $this->lastIP->inAddr()) {
			throw new \Exception('First IP is grater than second');
		}

		$this->firstIP = $ip;
	}

	/**
	 * @param IP $ip
	 * @throws \Exception
	 */
	public function setLastIP(IP $ip)
	{
		if($this->firstIP && $ip->inAddr() < $this->firstIP->inAddr()) {
			throw new \Exception('Last IP is less than first');
		}

		$this->lastIP = $ip;
	}

	/**
	 * @return IP
	 */
	public function getFirstIP()
	{
		return $this->firstIP;
	}

	/**
	 * @return IP
	 */
	public function getLastIP()
	{
		return $this->lastIP;
	}

	/**
	 * @return array
	 */
	public function getNetworks()
	{
		$span = $this->getSpanNetwork();

		$networks = array();

		if($span->getFirstIP()->inAddr() === $this->firstIP->inAddr()
			&& $span->getLastIP()->inAddr() === $this->lastIP->inAddr()
		) {
			$networks = array($span);
		} else {
			if($span->getFirstIP()->inAddr() !== $this->firstIP->inAddr()) {
				$excluded = $span->exclude($this->firstIP->prev());

				/**
				 * @var Network $network
				 */
				foreach ($excluded as $network) {
					if($network->getFirstIP()->inAddr() >= $this->firstIP->inAddr()) {
						$networks[] = $network;
					}
				}
			}

			if($span->getLastIP()->inAddr() !== $this->lastIP->inAddr()) {
				if(!$networks) {
					$excluded = $span->exclude($this->lastIP->next());
				} else {
					$excluded = array_pop($networks);
					$excluded = $excluded->exclude($this->lastIP->next());
				}

				foreach ($excluded as $network) {
					$networks[] = $network;
					if($network->getLastIP()->inAddr() === $this->lastIP->inAddr()) {
						break;
					}
				}
			}

		}

		return $networks;
	}

	/**
	 * @return Network
	 */
	public function getSpanNetwork()
	{
		$xorIP = IP::parseInAddr($this->getFirstIP()->inAddr() ^ $this->getLastIP()->inAddr());

		preg_match('/^(0*)/', $xorIP->toBin(), $match);

		$prefixLength = strlen($match[1]);

		$ip = IP::parseBin(str_pad(substr($this->getFirstIP()->toBin(), 0, $prefixLength), $xorIP->getMaxPrefixLength(), '0'));

		return new Network($ip, Network::prefix2netmask($prefixLength, $ip->getVersion()));
	}

	/**
	 * @return IP
	 */
	public function current()
	{
		return $this->firstIP->next($this->position);
	}

	/**
	 * @return int
	 */
	public function key()
	{
		return $this->position;
	}

	public function next()
	{
		++$this->position;
	}

	public function rewind()
	{
		$this->position = 0;
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		return $this->firstIP->next($this->position)->inAddr() <= $this->lastIP->next($this->position)->inAddr();
	}

	/**
	 * @return int
	 */
	public function count()
	{
		if($this->firstIP->getVersion() === IP::IP_V4) {
			$count = $this->lastIP->toLong() - $this->firstIP->toLong();
		} else {
			$count = bcsub($this->lastIP->toLong(), $this->firstIP->toLong());
		}

		return $count;
	}

}
