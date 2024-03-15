<?php
namespace Packetery\Module;

class PacketaPluginAssets {
    /** @var string[] */
	private array $scriptFiles;
    /** @var string[] */
	private array $styleFiles;

	/**
	 * @param array $scriptFiles
	 * @param array $styleFiles
	 */
    public function __construct(array $scriptFiles = [], array $styleFiles = []) {
        $this->scriptFiles = $scriptFiles;
        $this->styleFiles = $styleFiles;
    }

	/**
	 * @param array $scriptFiles
	 *
	 * @return void
	 */
	public function setScriptFiles(array $scriptFiles): void {
        $this->scriptFiles = $scriptFiles;
    }

	/**
	 * @return array|string[]
	 */
    public function getScriptFiles(): array {
        return $this->scriptFiles;
    }

	/**
	 * @param array $styleFiles
	 *
	 * @return void
	 */
    public function setStyleFiles(array $styleFiles): void {
        $this->styleFiles = $styleFiles;
    }

	/**
	 * @return array|string[]
	 */
    public function getStyleFiles(): array {
        return $this->styleFiles;
    }
}
