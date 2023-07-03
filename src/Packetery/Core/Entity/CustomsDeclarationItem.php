<?php
/**
 * Class CustomsDeclarationItem
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Core\Entity;

/**
 * Class CustomsDeclarationItem
 */
class CustomsDeclarationItem {

	/**
	 * ID.
	 *
	 * @var string|null
	 */
	private $id;

	/**
	 * Customs declaration.
	 *
	 * @var string
	 */
	private $customsDeclarationId;

	/**
	 * Code.
	 *
	 * @var string
	 */
	private $customsCode;

	/**
	 * Value.
	 *
	 * @var float
	 */
	private $value;

	/**
	 * Product name in english.
	 *
	 * @var string
	 */
	private $productNameEn;

	/**
	 * Product name.
	 *
	 * @var string|null
	 */
	private $productName;

	/**
	 * Amount.
	 *
	 * @var int
	 */
	private $unitsCount;

	/**
	 * Country code.
	 *
	 * @var string
	 */
	private $countryOfOrigin;

	/**
	 * Weight.
	 *
	 * @var float
	 */
	private $weight;

	/**
	 * Tells if contains food or book.
	 *
	 * @var bool
	 */
	private $isFoodOrBook;

	/**
	 * Tells if contains VOC.
	 *
	 * @var bool
	 */
	private $isVoc;

	/**
	 * Constructor.
	 *
	 * @param string $customsDeclarationId Customs declaration.
	 * @param string $customsCode Code.
	 * @param float  $value Value.
	 * @param string $productNameEn Product name in english.
	 * @param int    $unitsCount Amount.
	 * @param string $countryOfOrigin Country of origin.
	 * @param float  $weight Weight.
	 */
	public function __construct(
		string $customsDeclarationId,
		string $customsCode,
		float $value,
		string $productNameEn,
		int $unitsCount,
		string $countryOfOrigin,
		float $weight
	) {
		$this->customsDeclarationId = $customsDeclarationId;
		$this->customsCode          = $customsCode;
		$this->value                = $value;
		$this->productNameEn        = $productNameEn;
		$this->unitsCount           = $unitsCount;
		$this->countryOfOrigin      = $countryOfOrigin;
		$this->weight               = $weight;
	}

	/**
	 * Country of origin.
	 *
	 * @return string
	 */
	public function getCountryOfOrigin(): string {
		return $this->countryOfOrigin;
	}

	/**
	 * Customs code.
	 *
	 * @return string
	 */
	public function getCustomsCode(): string {
		return $this->customsCode;
	}

	/**
	 * Gets customs declaration.
	 *
	 * @return string
	 */
	public function getCustomsDeclarationId(): string {
		return $this->customsDeclarationId;
	}

	/**
	 * Gets ID.
	 *
	 * @return string|null
	 */
	public function getId(): ?string {
		return $this->id;
	}

	/**
	 * Sets ID.
	 *
	 * @param string|null $id ID.
	 * @return void
	 */
	public function setId( ?string $id ): void {
		$this->id = $id;
	}

	/**
	 * Gets product name.
	 *
	 * @return string|null
	 */
	public function getProductName(): ?string {
		return $this->productName;
	}

	/**
	 * Sets product name.
	 *
	 * @param string|null $productName Product name.
	 * @return void
	 */
	public function setProductName( ?string $productName ): void {
		$this->productName = $productName;
	}

	/**
	 * Product name english.
	 *
	 * @return string
	 */
	public function getProductNameEn(): string {
		return $this->productNameEn;
	}

	/**
	 * Amount.
	 *
	 * @return int
	 */
	public function getUnitsCount(): int {
		return $this->unitsCount;
	}

	/**
	 * Gets value.
	 *
	 * @return float
	 */
	public function getValue(): float {
		return $this->value;
	}

	/**
	 * Gets weight.
	 *
	 * @return float
	 */
	public function getWeight(): float {
		return $this->weight;
	}

	/**
	 * Tells if it contains food or book.
	 *
	 * @return bool
	 */
	public function isFoodOrBook(): bool {
		return $this->isFoodOrBook;
	}

	/**
	 * Sets book or food flag.
	 *
	 * @param bool $isFoodOrBook Food or book.
	 * @return void
	 */
	public function setIsFoodOrBook( bool $isFoodOrBook ): void {
		$this->isFoodOrBook = $isFoodOrBook;
	}

	/**
	 * Tells if it contains VOC.
	 *
	 * @return bool
	 */
	public function isVoc(): bool {
		return $this->isVoc;
	}

	/**
	 * Sets VOC flag.
	 *
	 * @param bool $isVoc VOC.
	 * @return void
	 */
	public function setIsVoc( bool $isVoc ): void {
		$this->isVoc = $isVoc;
	}
}
