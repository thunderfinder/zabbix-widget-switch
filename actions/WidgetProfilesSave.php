<?php declare(strict_types = 1);

namespace Modules\SwitchWidget\Actions;

use CController;
use CControllerResponseData;

class WidgetProfilesSave extends CController {
	protected function init(): void {
	}

	protected function checkInput(): bool {
			return $this->validateInput([
				'preset_id' => 'required|int32',
				'name' => 'required|string|not_empty',
				'row_count' => 'required|int32',
				'ports_per_row' => 'required|int32',
				'sfp_ports' => 'required|int32',
				'switch_size' => 'required|int32',
				'switch_brand' => 'string',
				'switch_model' => 'string'
			]);
	}

	protected function checkPermissions(): bool {
		return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
	}

	protected function doAction(): void {
		$preset_id = (int) $this->getInput('preset_id');
		if ($preset_id < 1 || $preset_id > 7) {
			$this->respond(false, 'Invalid preset id.');
			return;
		}

		$strip_ctrl = static fn(string $val): string => (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $val);
		$name = mb_substr($strip_ctrl(trim((string) $this->getInput('name'))), 0, 15);
		$row_count = max(1, min(24, (int) $this->getInput('row_count')));
		$ports_per_row = max(1, min(48, (int) $this->getInput('ports_per_row')));
		$sfp_ports = max(0, min(32, (int) $this->getInput('sfp_ports')));
		$switch_size = max(40, min(100, (int) $this->getInput('switch_size')));
		$switch_brand = mb_substr($strip_ctrl(trim((string) $this->getInput('switch_brand', 'NETSWITCH'))), 0, 30);
		$switch_model = mb_substr($strip_ctrl(trim((string) $this->getInput('switch_model', 'SW-24G'))), 0, 30);

		$file = __DIR__.'/../profiles.json';
		$data = [];
		if (is_readable($file)) {
			$raw = file_get_contents($file);
			if ($raw !== false) {
				$decoded = json_decode($raw, true);
				if (is_array($decoded)) {
					for ($i = 1; $i <= 7; $i++) {
						$k = (string) $i;
						if (isset($decoded[$k]) && is_array($decoded[$k])) {
							$data[$k] = $decoded[$k];
						}
					}
				}
			}
		}

		$key = (string) $preset_id;
		$data[$key] = [
			'name' => $name !== '' ? $name : 'Profile '.$preset_id,
			'row_count' => $row_count,
			'ports_per_row' => $ports_per_row,
			'sfp_ports' => $sfp_ports,
			'switch_size' => $switch_size,
			'switch_brand' => $switch_brand !== '' ? $switch_brand : 'NETSWITCH',
			'switch_model' => $switch_model !== '' ? $switch_model : 'SW-24G'
		];

		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			$this->respond(false, 'Failed to encode profile data.');
			return;
		}
		$json .= "\n";

		// Atomic write: write to unique temporary file with lock, then rename to target file.
		$tmp_file = $file.'.tmp.'.bin2hex(random_bytes(6));
		if (@file_put_contents($tmp_file, $json, LOCK_EX) === false) {
			$this->respond(false, 'Cannot write profiles.json on server (check file permissions).');
			return;
		}

		// Ensure file retains group-writable permissions (0664) required for shared web server setups.
		@chmod($tmp_file, 0664);

		if (!@rename($tmp_file, $file)) {
			if (file_exists($tmp_file)) {
				@unlink($tmp_file);
			}
			$this->respond(false, 'Cannot write profiles.json on server (check file permissions).');
			return;
		}

		$this->respond(true, '', $data);
	}

	private function respond(bool $saved, string $error = '', array $profiles = []): void {
		$this->setResponse(new CControllerResponseData([
			'main_block' => json_encode([
				'saved' => $saved,
				'error' => $error,
				'profiles' => $profiles
			])
		]));
	}
}
