<?php

namespace Setup\Maintenance;

use Cake\Core\Configure;

/**
 * Handle maintenance / down-time of the application.
 *
 * @author Mark Scherer
 * @license MIT
 */
class Maintenance {

	/**
	 * @var string
	 */
	public $file;

	/**
	 * @var string
	 */
	public $template = 'maintenance.ctp';

	public function __construct() {
		$this->file = TMP . 'maintenance.txt';
	}

	/**
	 * Check if maintenance mode is on.
	 *
	 * If overwritable, it will set Configure value 'Maintenance.overwrite' with the
	 * corresponding IP so the SetupComponent can trigger a warning message here.
	 *
	 * @param string|null $ipAddress If passed it allows access when it matches whitelisted IPs.
	 * @return bool Success
	 */
	public function isMaintenanceMode($ipAddress = null) {
		if ($ipAddress) {
			$this->enableDebugModeForWhitelist($ipAddress);
		}

		if (!file_exists($this->file)) {
			return false;
		}

		$content = file_get_contents($this->file);
		if ($content === false) {
			return false;
		}

		if ($content > 0 && $content < time()) {
			$this->setMaintenanceMode(false);

			return false;
		}

		if ($ipAddress) {
			$file = TMP . 'maintenanceOverride-' . $this->_slugIp($ipAddress) . '.txt';
			if (file_exists($file)) {
				Configure::write('Maintenance.overwrite', $ipAddress);

				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $ipAddress
	 * @return void
	 */
	public function enableDebugModeForWhitelist($ipAddress) {
		if (!$ipAddress) {
			return;
		}
		$file = TMP . 'maintenanceOverride-' . $this->_slugIp($ipAddress) . '.txt';
		if (!file_exists($file)) {
			return;
		}
		Configure::write('debug', (bool)file_get_contents($file));
	}

	/**
	 * Set maintenance mode.
	 *
	 * Integer (in minutes) to activate with timeout.
	 * Using 0 it will have no timeout.
	 *
	 * @param int|false $value False to deactivate, or Integer to activate.
	 * @return bool Success
	 */
	public function setMaintenanceMode($value) {
		if ($value === false) {
			if (!file_exists($this->file)) {
				return true;
			}

			return unlink($this->file);
		}

		if ($value) {
			$value = time() + $value * MINUTE;
		}

		return (bool)file_put_contents($this->file, $value);
	}

	/**
	 * Get the whitelist or add new IPs.
	 * Note: Expects IPs to be valid.
	 *
	 * @param array<string> $newIps IP addressed to be added to the whitelist.
	 * @param int $debugMode
	 * @return array<string>|true Boolean Success for adding, an array of all whitelisted IPs otherwise.
	 */
	public function whitelist($newIps = [], $debugMode = 0) {
		if ($newIps) {
			foreach ($newIps as $ip) {
				$this->_addToWhitelist($ip, $debugMode);
			}

			return true;
		}

		/** @var array<string> $files */
		$files = glob(TMP . 'maintenanceOverride-*.txt');
		$ips = [];
		foreach ($files as $file) {
			$ip = pathinfo($file, PATHINFO_FILENAME);
			$ip = substr($ip, strpos($ip, '-') + 1);
			$ips[] = $this->_unslugIp($ip);
		}

		return $ips;
	}

	/**
	 * Clear whitelist. If IPs are passed, only those will be removed, otherwise all.
	 *
	 * @param array<string> $ips
	 * @return bool Success
	 */
	public function clearWhitelist($ips = []) {
		/** @var array<string> $files */
		$files = glob(TMP . 'maintenanceOverride-*.txt');
		foreach ($files as $file) {
			$ip = pathinfo($file, PATHINFO_FILENAME);
			$ip = substr($ip, strpos($ip, '-') + 1);
			if (!$ips || in_array($this->_unslugIp($ip), $ips, true)) {
				if (!unlink($file)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * MaintenanceLib::_addToWhitelist()
	 *
	 * @param string $ip Valid IP address.
	 * @param int $debugMode
	 * @return bool Success.
	 */
	protected function _addToWhitelist(string $ip, $debugMode = 0) {
		$file = TMP . 'maintenanceOverride-' . $this->_slugIp($ip) . '.txt';
		if (!file_put_contents($file, $debugMode)) {
			return false;
		}

		return true;
	}

	/**
	 * Handle special chars in IPv6.
	 *
	 * @param string $ip
	 * @return string
	 */
	protected function _slugIp(string $ip) {
		return str_replace(':', '#', $ip);
	}

	/**
	 * Handle special chars in IPv6.
	 *
	 * @param string $ip
	 * @return string
	 */
	protected function _unslugIp(string $ip) {
		return str_replace('#', ':', $ip);
	}

}
