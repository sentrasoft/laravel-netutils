<?php
namespace Sentrasoft\Netutils;

use Sentrasoft\Netutils\IP;
use Sentrasoft\Netutils\Ping;

class Network implements \Iterator, \Countable
{
	use PropertyTrait;

	/**
	 * @var IP
	 */
	private $ip;
	/**
     * @var IP
     */
	private $netmask;
	/**
	 * @var int
	 */
	private $position = 0;

	/**
	 * @param IP $ip
	 * @param IP $netmask
	 */
	public function __construct($ip, $netmask)
	{
		$this->setIP(new IP($ip));
		$this->setNetmask(new IP($netmask));

		// $this->setIP(IP::parse($ip));
		// $this->setNetmask(IP::parse($netmask));

		// $this->setIP($ip);
		// $this->setNetmask($netmask);
	}

	/**
	 * @param string ip
	 * @throws \Exception
	 */
	// public function ip($ip)
	// {
	// 	return new IP($ip);
	// }

	/**
	 * @param IP $firstIP
	 * @param IP $lastIP
	 * @throws \Exception
	 */
	// public function range($firstIP, $lastIP)
	// {
	// 	return new Range($firstIP, $lastIP);
	// }

	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getCIDR();
	}

	/**
	 * @param string $data
	 * @return Network
	 */
	public static function parse($data)
	{
		if (strpos($data,'/')) {
			list($ip, $prefixLength) = explode('/', $data, 2);
			$ip      = IP::parse($ip);
			$netmask = self::prefix2netmask((int)$prefixLength, $ip->getVersion());
		} elseif (strpos($data,' ')) {
			list($ip, $netmask) = explode(' ', $data, 2);
			$ip      = IP::parse($ip);
			$netmask = IP::parse($netmask);
		} else {
			$ip      = IP::parse($data);
			$netmask = self::prefix2netmask($ip->getMaxPrefixLength(), $ip->getVersion());
		}

		return new self($ip, $netmask);
		// $this->setIP(IP::parse($ip));
		// $this->setNetmask(IP::parse($netmask));
	}

	/**
	 * @param int $prefixLength
	 * @param string $version
	 * @return IP
	 * @throws \Exception
	 */
	public static function prefix2netmask($prefixLength, $version)
	{
		if (!in_array($version, array(IP::IP_V4, IP::IP_V6))) {
			throw new \Exception("Wrong IP version");
		}

		$maxPrefixLength = $version === IP::IP_V4
			? IP::IP_V4_MAX_PREFIX_LENGTH
			: IP::IP_V6_MAX_PREFIX_LENGTH;

		if (!is_numeric($prefixLength)
			|| !($prefixLength >= 0 && $prefixLength <= $maxPrefixLength)
		) {
			throw new \Exception('Invalid prefix length');
		}

		$binIP = str_pad(str_pad('', (int)$prefixLength, '1'), $maxPrefixLength, '0');

		return IP::parseBin($binIP);
	}

	/**
	 * @param IP ip
	 * @return int
	 */
	public static function netmask2prefix(IP $ip)
	{
		return strlen(rtrim($ip->bin, 0));
	}

	/**
	 * @param IP ip
	 * @throws \Exception
	 */
	public function setIP(IP $ip)
	{
		if (isset($this->netmask) && $this->netmask->getVersion() !== $ip->getVersion()) {
			throw new \Exception('IP version is not same as Netmask version');
		}

		$this->ip = $ip;
	}

	/**
	 * @param IP ip
	 * @throws \Exception
	 */
	public function setNetmask(IP $ip)
	{
		if (!preg_match('/^1*0*$/',$ip->bin)) {
			throw new \Exception('Invalid Netmask address format');
		}

		if(isset($this->ip) && $ip->getVersion() !== $this->ip->getVersion()) {
			throw new \Exception('Netmask version is not same as IP version');
		}

		$this->netmask = $ip;
	}

	/**
	 * @param int $prefixLength
	 */
	public function setPrefixLength($prefixLength)
	{
		$this->setNetmask(self::prefix2netmask((int)$prefixLength, $this->ip->getVersion()));
	}

	/**
	 * @return IP
	 */
	public function getIP()
	{
		return $this->ip;
	}

	/**
	 * @return IP
	 */
	public function getNetmask()
	{
		return $this->netmask;
	}

	public function getDefaultNetmask()
	{
		$ip = explode('.',$this->ip);
		$head = intval($ip[0]);
		$ret = '255.255.255.0';

		switch ($head) {
			case ($head >= 1 && $head <= 127):
				$ret = '255.0.0.0';
				break;
			case ($head >= 128 && $head <= 191):
				$ret = '255.255.0.0';
				break;
			case ($head >= 192 && $head <= 223):
				$ret = '255.255.255.0';
				break;

			default:
				$ret = '255.255.255.0';
				break;
		}

		return $ret;

	}

	/**
	 * @return IP
	 */
	public function getNetwork()
	{
		return new IP(inet_ntop($this->getIP()->inAddr() & $this->getNetmask()->inAddr()));
	}

	/**
	 * @return int
	 */
	public function getPrefixLength()
	{
		return self::netmask2prefix($this->getNetmask());
	}

	/**
	 * @return string
	 */
	public function getCIDR()
	{
		return sprintf('%s/%s', $this->getNetwork(), $this->getPrefixLength());
	}

	/**
	 * @return IP
	 */
	public function getWildcard()
	{
		return new IP(inet_ntop(~$this->getNetmask()->inAddr()));
	}

	/**
	 * @return IP
	 */
	public function getBroadcast()
	{
		// return inet_ntop($this->getNetwork()->inAddr() | ~$this->getNetmask()->inAddr());
		return new IP(inet_ntop($this->getNetwork()->inAddr() | ~$this->getNetmask()->inAddr()));
	}

	/**
	 * @return IP
	 */
	public function getFirstIP()
	{
		return $this->getNetwork();
	}

	/**
     * @return IP
     */
	public function getLastIP()
	{
		return $this->getBroadcast();
	}

	/**
	 * @param bool $largeNumber
	 * @return number|string
	 */
	public function getBlockSize()
	{
		$maxPrefixLength = $this->ip->getMaxPrefixLength();
		$prefixLength = $this->getPrefixLength();

		if ($this->ip->getVersion() === IP::IP_V6) {
			return bcpow('2', (string)($maxPrefixLength - $prefixLength));
		}

		return pow(2, $maxPrefixLength - $prefixLength);
	}

	/**
	 * @return IP
	 */
	public function getFirstHost()
	{
		$network = $this->getNetwork();

		if ($network->getVersion() === IP::IP_V4) {
			if ($this->getBlockSize() > 2) {
				return IP::parseBin(substr($network->bin, 0, $network->getMaxPrefixLength() - 1) . '1');
			}
		}

		return $network;

	}

	/**
	 * @return IP
	 */
	public function getLastHost()
	{
		$broadcast = $this->getBroadcast();

		if ($broadcast->getVersion() === IP::IP_V4) {
			if ($this->getBlockSize() > 2) {
				return IP::parseBin(substr($broadcast->bin, 0, $broadcast->getMaxPrefixLength() - 1) . '0');
			}
		}

		return $broadcast;

	}

	/**
	 * @return number|string
	 */
	public function getHostsCount()
	{
		$blockSize = $this->getBlockSize();

		if ($this->ip->getVersion() === IP::IP_V4) {
			return $blockSize > 2 ? $blockSize - 2 : $blockSize;
		}

		return $blockSize;
	}

	/**
	 * @param IP|Network $exclude
	 * @return array
	 * @throws \Exception
	 */
	public function exclude($exclude)
	{
		$exclude = self::parse($exclude);

		if($exclude->getFirstIP()->inAddr() > $this->getLastIP()->inAddr()
			|| $exclude->getLastIP()->inAddr() < $this->getFirstIP()->inAddr()
		) {
			throw new \Exception('Exclude subnet not within target network');
		}

		$networks = array();

		$newPrefixLength = $this->getPrefixLength() + 1;

		$lower = clone $this;
		$lower->setPrefixLength($newPrefixLength);

		$upper = clone $lower;
		$upper->setIP($lower->getLastIP()->next());

		while ($newPrefixLength <= $exclude->getPrefixLength()) {
			$range = new Range($lower->getFirstIP(), $lower->getLastIP());
			if($range->contains($exclude)) {
				$matched   = $lower;
				$unmatched = $upper;
			} else {
				$matched   = $upper;
				$unmatched = $lower;
			}

			$networks[] = clone $unmatched;

			if(++$newPrefixLength > $this->getNetwork()->getMaxPrefixLength()) break;

			$matched->setPrefixLength($newPrefixLength);
			$unmatched->setPrefixLength($newPrefixLength);
			$unmatched->setIP($matched->getLastIP()->next());
		}

		sort($networks);

		return $networks;
	}

	/**
	* The exec method uses the possibly insecure exec() function, which passes
	* the input to the system. This is potentially VERY dangerous if you pass in
	* any user-submitted data. Be SURE you sanitize your inputs!
	*
	* @return string
	*   macAddr, in string.
	*/
	public function getMac() {
		$macAddr = '00:00:00:00:00:00';

		$host = escapeshellcmd($this->ip);
		$ping = new Ping($host);
		if ($ping->ping()) {
			// Exec string for Windows-based systems.
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// -a = Display current ARP entries by introgating current protocol data.
				$exec_string = 'arp -a ' . $host;
				exec($exec_string, $output, $return);

				// $lines=explode("\n", $output);

				#look for the output line describing our IP address
				foreach($output as $line)
				{
					$cols=preg_split('/\s+/', trim($line));
					if (trim($cols[0])==$this->ip)
					{
						$macAddr=str_replace('-', ':', $cols[1]);
					}
				}
			}
			// Exec string for UNIX-based systems (Mac, Linux).
			else {
			// -n = Don't resolve names.
				$exec_string = 'arp -n ' . $host;
				exec($exec_string, $output, $return);

				// $lines=explode("\n", $output);

				#look for the output line describing our IP address
				foreach($output as $line)
				{
					$cols=preg_split('/\s+/', trim($line));
					if (trim($cols[0])==$this->ip)
					{
						if (substr(trim($cols[1]),0,1) != '(') {
							$macAddr=$cols[2];
						}
					}
				}
			}
		}

		return strtolower($macAddr);
		// return $return;
	}

	/**
	 * @return array
	 */
	public function getInfo()
	{
		$info = array();

		$reflect = new \ReflectionClass($this);

		foreach ($reflect->getMethods() as $method) {
			if(strpos($method->name, 'get') === 0 && $method->name !== __FUNCTION__) {
				$property = substr($method->name, 3);

				if($property !== 'IP' && $property !== 'CIDR') {
					$property = lcfirst($property);
				}

				$info[$property] = is_object($this->{$method->name}())
					? (string)$this->{$method->name}()
					: $this->{$method->name}();
			}
		}

		return $info;
	}

	/**
	* @return IP
	*/
	public function current()
	{
		return $this->getFirstHost()->next($this->position);
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
		return $this->getFirstHost()->next($this->position)->inAddr() <= $this->getLastHost()->inAddr();
	}

	/**
	* @return int
	*/
	public function count()
	{
		return $this->getHostsCount();
	}

}
