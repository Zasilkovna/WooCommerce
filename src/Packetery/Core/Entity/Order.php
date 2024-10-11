<?php
/**
 * Class Order
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Core\Entity;

use Packetery\Core\CoreHelper;
use DateTimeImmutable;

/**
 * Class Order
 *
 * @package Packetery\Order
 */
class Order {

	/**
	 * Order id.
	 *
	 * @var string
	 */
	private $number;

	/**
	 * Car Delivery id.
	 *
	 * @var string
	 */
	private $carDeliveryId;

	/**
	 * Custom order number.
	 *
	 * @var string|null
	 */
	private $customNumber;

	/**
	 * Order carrier object.
	 *
	 * @var Carrier
	 */
	private $carrier;

	/**
	 * Order pickup point object.
	 *
	 * @var PickupPoint|null
	 */
	private $pickupPoint;

	/**
	 * Customer name.
	 *
	 * @var string|null
	 */
	private $name;

	/**
	 * Customer surname.
	 *
	 * @var string|null
	 */
	private $surname;

	/**
	 * Customer e-mail.
	 *
	 * @var string|null
	 */
	private $email;

	/**
	 * Customer phone.
	 *
	 * @var string|null
	 */
	private $phone;

	/**
	 * Order value.
	 *
	 * @var float|null
	 */
	private $value;

	/**
	 * Sender label.
	 *
	 * @var string|null
	 */
	private $eshop;

	/**
	 * Address.
	 *
	 * @var Address|null
	 */
	private $address;

	/**
	 * Size.
	 *
	 * @var Size|null
	 */
	private $size;

	/**
	 * Package weight, set or calculated.
	 *
	 * @var float|null
	 */
	private $weight;

	/**
	 * Calculated package weight.
	 *
	 * @var float|null
	 */
	private $calculatedWeight;

	/**
	 * Cash on delivery value.
	 *
	 * @var float|null
	 */
	private $cod;

	/**
	 * Packet note.
	 *
	 * @var string|null
	 */
	private $note;

	/**
	 * Adult content presence flag.
	 *
	 * @var bool|null
	 */
	private $adultContent;

	/**
	 * Packet ID
	 *
	 * @var string|null
	 */
	private $packetId;

	/**
	 * Packet ID.
	 *
	 * @var string|null
	 */
	private $packetClaimId;

	/**
	 * Packet password.
	 *
	 * @var string|null
	 */
	private $packetClaimPassword;

	/**
	 * Packet ID
	 *
	 * @var string|null
	 */
	private $packetStatus;

	/**
	 * Tells if is packet submitted.
	 *
	 * @var bool
	 */
	private $isExported;

	/**
	 * Tells if is packet submitted.
	 *
	 * @var bool
	 */
	private $isLabelPrinted;

	/**
	 * Packet currency.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Carrier number.
	 *
	 * @var string|null
	 */
	private $carrierNumber;

	/**
	 * Address validated.
	 *
	 * @var bool
	 */
	private $addressValidated;

	/**
	 * ISO 3166-1 alpha-2 code, lowercase.
	 *
	 * @var string|null
	 */
	private $shippingCountry;

	/**
	 * Last message built from Packeta API response.
	 *
	 * @var string|null
	 */
	private $lastApiErrorMessage;

	/**
	 * Last API error datetime.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private $lastApiErrorDateTime;

	/**
	 * Deliver on
	 *
	 * @var DateTimeImmutable|null
	 */
	private $deliverOn;

	/**
	 * Customs declaration.
	 *
	 * @var CustomsDeclaration|null
	 */
	private $customsDeclaration;

	/**
	 * Order entity constructor.
	 *
	 * @param string  $number  Order id.
	 * @param Carrier $carrier Carrier entity.
	 */
	public function __construct(
		string $number,
		Carrier $carrier
	) {
		$this->number           = $number;
		$this->carrier          = $carrier;
		$this->isExported       = false;
		$this->isLabelPrinted   = false;
		$this->addressValidated = false;
	}

	/**
	 * Gets Car Delivery id.
	 *
	 * @return string
	 */
	public function getCarDeliveryId(): ?string {
		return $this->carDeliveryId;
	}

	/**
	 * Sets Car Delivery id.
	 *
	 * @param string $carDeliveryId Car delivery ID.
	 * @return void
	 */
	public function setCarDeliveryId( string $carDeliveryId ): void {
		$this->carDeliveryId = $carDeliveryId;
	}

	/**
	 * Sets custom declaration.
	 *
	 * @param CustomsDeclaration|null $customsDeclaration Customs declaration.
	 *
	 * @return void
	 */
	public function setCustomsDeclaration( ?CustomsDeclaration $customsDeclaration ): void {
		$this->customsDeclaration = $customsDeclaration;
	}

	/**
	 * Gets customs declaration.
	 *
	 * @return CustomsDeclaration|null
	 */
	public function getCustomsDeclaration(): ?CustomsDeclaration {
		return $this->customsDeclaration;
	}

	/**
	 * Has customs declaration items.
	 *
	 * @return bool
	 */
	public function hasCustomsDeclaration(): bool {
		return null !== $this->customsDeclaration && [] !== $this->customsDeclaration->getItems();
	}

	/**
	 * Tells if customs declaration has to be filled to submit packet.
	 *
	 * @return bool
	 */
	public function hasToFillCustomsDeclaration(): bool {
		return $this->carrier->requiresCustomsDeclarations() &&
			false === $this->hasCustomsDeclaration();
	}

	/**
	 * Gets custom number.
	 *
	 * @return string|null
	 */
	public function getCustomNumber(): ?string {
		return $this->customNumber;
	}

	/**
	 * Sets custom number.
	 *
	 * @param string|null $customNumber Custom number.
	 *
	 * @return void
	 */
	public function setCustomNumber( ?string $customNumber ): void {
		$this->customNumber = $customNumber;
	}

	/**
	 * Is address validated?
	 *
	 * @return bool
	 */
	public function isAddressValidated(): bool {
		return $this->addressValidated;
	}

	/**
	 * Sets address validation flag.
	 *
	 * @param bool $addressValidated Address validated.
	 *
	 * @return void
	 */
	public function setAddressValidated( bool $addressValidated ): void {
		$this->addressValidated = $addressValidated;
	}

	/**
	 * Checks if is home delivery. In that case pointId is not set.
	 *
	 * @return bool
	 */
	public function isHomeDelivery(): bool {
		return ( ! $this->carrier->hasPickupPoints() && ! $this->isCarDelivery() );
	}

	/**
	 * Checks if is home delivery. In that case pointId is not set.
	 *
	 * @return bool
	 */
	public function isCarDelivery(): bool {
		return $this->carrier->isCarDelivery();
	}

	/**
	 * Tells if order has to be shipped to pickup point, run by Packeta or an external carrier.
	 *
	 * @return bool
	 */
	public function isPickupPointDelivery(): bool {
		return $this->carrier->hasPickupPoints();
	}

	/**
	 * Checks if order uses external carrier.
	 *
	 * @return bool
	 */
	public function isExternalCarrier(): bool {
		return is_numeric( $this->getCarrier()->getId() );
	}

	/**
	 * Check if delivery method is internal packetery pickup point
	 *
	 * @return bool
	 */
	public function isPacketaInternalPickupPoint(): bool {
		return ! $this->isExternalCarrier() && $this->isPickupPointDelivery();
	}

	/**
	 * Gets pickup point/carrier id.
	 *
	 * @return int|null
	 */
	public function getPickupPointOrCarrierId(): ?int {
		if ( $this->isExternalCarrier() ) {
			return (int) $this->getCarrier()->getId();
		}

		if ( null === $this->pickupPoint ) {
			return null;
		}

		// Typing to int is safe in case of internal pickup points.
		return (int) $this->pickupPoint->getId();
	}

	/**
	 * Sets pickup point.
	 *
	 * @param PickupPoint $pickupPoint Pickup point.
	 */
	public function setPickupPoint( PickupPoint $pickupPoint ): void {
		$this->pickupPoint = $pickupPoint;
	}

	/**
	 * Sets delivery address.
	 *
	 * @param Address $address Delivery address.
	 */
	public function setDeliveryAddress( Address $address ): void {
		$this->address = $address;
	}

	/**
	 * Sets packet size.
	 *
	 * @param Size $size Size.
	 */
	public function setSize( Size $size ): void {
		$this->size = $size;
	}

	/**
	 * Sets number.
	 *
	 * @param string $number Number.
	 */
	public function setNumber( string $number ): void {
		$this->number = $number;
	}

	/**
	 * Sets phone.
	 *
	 * @param string $name Name.
	 */
	public function setName( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Sets surname.
	 *
	 * @param string $surname Surname.
	 */
	public function setSurname( string $surname ): void {
		$this->surname = $surname;
	}

	/**
	 * Sets e-mail.
	 *
	 * @param string $email E-mail.
	 */
	public function setEmail( string $email ): void {
		$this->email = $email;
	}

	/**
	 * Sets eshop.
	 *
	 * @param string|null $eshop Eshop.
	 *
	 * @return void
	 */
	public function setEshop( ?string $eshop ): void {
		$this->eshop = $eshop;
	}

	/**
	 * Sets phone.
	 *
	 * @param string $phone Phone.
	 */
	public function setPhone( string $phone ): void {
		$this->phone = $phone;
	}

	/**
	 * Sets value.
	 *
	 * @param float|null $value Value.
	 */
	public function setValue( ?float $value ): void {
		$this->value = $value;
	}

	/**
	 * Sets COD.
	 *
	 * @param float|null $cod COD.
	 */
	public function setCod( ?float $cod ): void {
		$this->cod = $cod;
	}

	/**
	 * Gets packet currency.
	 *
	 * @return string
	 */
	public function getCurrency(): string {
		return $this->currency;
	}

	/**
	 * Sets packet currency.
	 *
	 * @param string $currency Currency.
	 *
	 * @return void
	 */
	public function setCurrency( string $currency ): void {
		$this->currency = $currency;
	}

	/**
	 * Sets packet note.
	 *
	 * @param string $note Packet note.
	 */
	public function setNote( string $note ): void {
		$this->note = $note;
	}

	/**
	 * Sets adult content presence flag.
	 *
	 * @param bool|null $adultContent Adult content presence flag.
	 */
	public function setAdultContent( ?bool $adultContent ): void {
		$this->adultContent = $adultContent;
	}

	/**
	 * Sets packet id.
	 *
	 * @param string|null $packetId Packet id.
	 */
	public function setPacketId( ?string $packetId ): void {
		$this->packetId = $packetId;
	}

	/**
	 * Sets packet claim ID.
	 *
	 * @param string|null $packetClaimId Packet claim ID.
	 *
	 * @return void
	 */
	public function setPacketClaimId( ?string $packetClaimId ): void {
		$this->packetClaimId = $packetClaimId;
	}

	/**
	 * Packet claim password.
	 *
	 * @param string|null $packetClaimPassword Packet claim password.
	 *
	 * @return void
	 */
	public function setPacketClaimPassword( ?string $packetClaimPassword ): void {
		$this->packetClaimPassword = $packetClaimPassword;
	}

	/**
	 * Sets packet status.
	 *
	 * @param string|null $packetStatus Packet status.
	 *
	 * @return void
	 */
	public function setPacketStatus( ?string $packetStatus ): void {
		$this->packetStatus = $packetStatus;
	}

	/**
	 * Sets is exported flag.
	 *
	 * @param bool $isExported Packet id.
	 */
	public function setIsExported( bool $isExported ): void {
		$this->isExported = $isExported;
	}

	/**
	 * Sets flag of label print.
	 *
	 * @param bool $isLabelPrinted Is label printed.
	 *
	 * @return void
	 */
	public function setIsLabelPrinted( bool $isLabelPrinted ): void {
		$this->isLabelPrinted = $isLabelPrinted;
	}

	/**
	 * Sets carrier number.
	 *
	 * @param string|null $carrierNumber Carrier number.
	 *
	 * @return void
	 */
	public function setCarrierNumber( ?string $carrierNumber ): void {
		$this->carrierNumber = $carrierNumber;
	}

	/**
	 * Sets weight.
	 *
	 * @param float|null $weight Weight.
	 *
	 * @return void
	 */
	public function setWeight( ?float $weight ): void {
		$this->weight = CoreHelper::simplifyWeight( $weight );
	}

	/**
	 * Sets calculated weight.
	 *
	 * @param float|null $weight Weight.
	 *
	 * @return void
	 */
	public function setCalculatedWeight( ?float $weight ): void {
		$this->calculatedWeight = CoreHelper::simplifyWeight( $weight );
	}

	/**
	 * Sets shipping country.
	 *
	 * @param string $shippingCountry ISO 3166-1 alpha-2 code, lowercase.
	 *
	 * @return void
	 */
	public function setShippingCountry( string $shippingCountry ): void {
		if ( '' === $shippingCountry ) {
			$this->shippingCountry = null;
		} else {
			$this->shippingCountry = $shippingCountry;
		}
	}

	/**
	 * Sets API response message.
	 *
	 * @param string|null $lastApiErrorMessage API response message.
	 *
	 * @return void
	 */
	public function setLastApiErrorMessage( ?string $lastApiErrorMessage ): void {
		$this->lastApiErrorMessage = $lastApiErrorMessage;
	}

	/**
	 * Sets API error date.
	 *
	 * @param \DateTimeImmutable|null $lastApiErrorDateTime API error date.
	 */
	public function setLastApiErrorDateTime( ?\DateTimeImmutable $lastApiErrorDateTime ): void {
		$this->lastApiErrorDateTime = $lastApiErrorDateTime;
	}

	/**
	 * Gets carrier object.
	 *
	 * @return Carrier
	 */
	public function getCarrier(): Carrier {
		return $this->carrier;
	}

	/**
	 * Gets pickup point object.
	 *
	 * @return PickupPoint|null
	 */
	public function getPickupPoint(): ?PickupPoint {
		return $this->pickupPoint;
	}

	/**
	 * Gets delivery address object.
	 *
	 * @return Address|null
	 */
	public function getDeliveryAddress(): ?Address {
		return $this->address;
	}

	/**
	 * Gets validated delivery address object.
	 *
	 * @return Address|null
	 */
	public function getValidatedDeliveryAddress(): ?Address {
		return ( $this->addressValidated ? $this->address : null );
	}

	/**
	 * Gets size object.
	 *
	 * @return Size|null
	 */
	public function getSize(): ?Size {
		return $this->size;
	}

	/**
	 * Packet ID
	 *
	 * @return string|null
	 */
	public function getPacketId(): ?string {
		return $this->packetId;
	}

	/**
	 * Packet barcode e.g Z123456789
	 *
	 * @return string|null
	 */
	public function getPacketBarcode(): ?string {
		return $this->packetId ? 'Z' . $this->packetId : null;
	}

	/**
	 * Packet Claim barcode e.g Z123456789
	 *
	 * @return string|null
	 */
	public function getPacketClaimBarcode(): ?string {
		return $this->packetClaimId ? 'Z' . $this->packetClaimId : null;
	}

	/**
	 * Get packet tracking url
	 *
	 * @return string|null
	 */
	public function getPacketTrackingUrl(): ?string {
		return $this->packetId ? sprintf( CoreHelper::TRACKING_URL, $this->packetId ) : null;
	}

	/**
	 * Get packet claim tracking url.
	 *
	 * @return string|null
	 */
	public function getPacketClaimTrackingUrl(): ?string {
		return $this->packetClaimId ? sprintf( CoreHelper::TRACKING_URL, $this->packetClaimId ) : null;
	}

	/**
	 * Gets packet claim ID.
	 *
	 * @return string|null
	 */
	public function getPacketClaimId(): ?string {
		return $this->packetClaimId;
	}

	/**
	 * Gets packet claim password.
	 *
	 * @return string|null
	 */
	public function getPacketClaimPassword(): ?string {
		return $this->packetClaimPassword;
	}

	/**
	 * Packet status.
	 *
	 * @return string|null
	 */
	public function getPacketStatus(): ?string {
		return $this->packetStatus;
	}

	/**
	 * Tells if packet claim creation is possible.
	 *
	 * @return bool
	 */
	public function isPacketClaimCreationPossible(): bool {
		return PacketStatus::DELIVERED === $this->packetStatus &&
			null === $this->packetClaimId;
	}

	/**
	 * Determines whether a packet is a claim, or otherwise.
	 *
	 * @param string $packetId Packet id.
	 *
	 * @return bool
	 */
	public function isPacketClaim( string $packetId ): bool {
		return $this->getPacketClaimId() === $packetId;
	}

	/**
	 * Tells if packet claim label print is possible.
	 *
	 * @return bool
	 */
	public function isPacketClaimLabelPrintPossible(): bool {
		return null !== $this->packetClaimId &&
			false === $this->isExternalCarrier() &&
			null !== $this->pickupPoint;
	}

	/**
	 * Tells if is packet submitted.
	 *
	 * @return bool
	 */
	public function isExported(): bool {
		return $this->isExported;
	}

	/**
	 * Gets weight.
	 *
	 * @return float|null
	 */
	public function getWeight(): ?float {
		return $this->weight;
	}

	/**
	 * Tells if order has manual weight set.
	 *
	 * @return bool
	 */
	public function hasManualWeight(): bool {
		return null !== $this->weight;
	}

	/**
	 * Gets final weight.
	 *
	 * @return float|null
	 */
	public function getFinalWeight(): ?float {
		return $this->weight ?? $this->calculatedWeight;
	}

	/**
	 * Gets calculated weight.
	 *
	 * @return float|null
	 */
	public function getCalculatedWeight(): ?float {
		return $this->calculatedWeight;
	}

	/**
	 * Gets length.
	 *
	 * @return float|null
	 */
	public function getLength(): ?float {
		if ( empty( $this->size ) ) {
			return null;
		}

		return $this->size->getLength();
	}

	/**
	 * Gets width.
	 *
	 * @return float|null
	 */
	public function getWidth(): ?float {
		if ( empty( $this->size ) ) {
			return null;
		}

		return $this->size->getWidth();
	}

	/**
	 * Gets height.
	 *
	 * @return float|null
	 */
	public function getHeight(): ?float {
		if ( empty( $this->size ) ) {
			return null;
		}

		return $this->size->getHeight();
	}

	/**
	 * Checks adult content presence.
	 *
	 * @return bool|null
	 */
	public function containsAdultContent(): ?bool {
		return $this->adultContent;
	}

	/**
	 * Gets order id.
	 *
	 * @return string|null
	 */
	public function getNumber(): ?string {
		return $this->number;
	}

	/**
	 * Gets customer name.
	 *
	 * @return string|null
	 */
	public function getName(): ?string {
		return $this->name;
	}

	/**
	 * Gets customer surname.
	 *
	 * @return string|null
	 */
	public function getSurname(): ?string {
		return $this->surname;
	}

	/**
	 * Gets order value.
	 *
	 * @return float|null
	 */
	public function getValue(): ?float {
		return $this->value;
	}

	/**
	 * Gets order COD value.
	 *
	 * @return float|null
	 */
	public function getCod(): ?float {
		return $this->cod;
	}

	/**
	 * Gets sender label.
	 *
	 * @return string|null
	 */
	public function getEshop(): ?string {
		return $this->eshop;
	}

	/**
	 * Gets customer e-mail.
	 *
	 * @return string|null
	 */
	public function getEmail(): ?string {
		return $this->email;
	}

	/**
	 * Gets delivery note.
	 *
	 * @return string|null
	 */
	public function getNote(): ?string {
		return $this->note;
	}

	/**
	 * Gets customer phone.
	 *
	 * @return string|null
	 */
	public function getPhone(): ?string {
		return $this->phone;
	}

	/**
	 * Is label printed?
	 *
	 * @return bool
	 */
	public function isLabelPrinted(): bool {
		return $this->isLabelPrinted;
	}

	/**
	 * Carrier number.
	 *
	 * @return string|null
	 */
	public function getCarrierNumber(): ?string {
		return $this->carrierNumber;
	}

	/**
	 * Gets shipping country.
	 *
	 * @return string|null
	 */
	public function getShippingCountry(): ?string {
		return $this->shippingCountry;
	}

	/**
	 * Tells if order has COD.
	 *
	 * @return bool
	 */
	public function hasCod(): bool {
		return ( null !== $this->getCod() );
	}

	/**
	 * Allows Adult Content
	 *
	 * @return bool
	 */
	public function allowsAdultContent(): bool {
		return $this->isPacketaInternalPickupPoint() || $this->getCarrier()->supportsAgeVerification();
	}

	/**
	 * Returns deliver on date
	 *
	 * @return DateTimeImmutable|null
	 */
	public function getDeliverOn(): ?DateTimeImmutable {
		return $this->deliverOn;
	}

	/**
	 * Sets deliver on date
	 *
	 * @param DateTimeImmutable|null $deliverOn Deliver on.
	 *
	 * @return void
	 */
	public function setDeliverOn( ?DateTimeImmutable $deliverOn ): void {
		$this->deliverOn = $deliverOn;
	}

	/**
	 * Formatted API error message.
	 *
	 * @return string|null
	 */
	public function getLastApiErrorMessage(): ?string {
		return $this->lastApiErrorMessage;
	}

	/**
	 * Gets last API error message date.
	 *
	 * @return \DateTimeImmutable|null
	 */
	public function getLastApiErrorDateTime(): ?\DateTimeImmutable {
		return $this->lastApiErrorDateTime;
	}

	/**
	 * Updates API error message and sets error message date accordingly.
	 *
	 * @param string|null $errorMessage Error message.
	 *
	 * @return void
	 */
	public function updateApiErrorMessage( ?string $errorMessage ): void {
		$this->setLastApiErrorMessage( $errorMessage );
		$this->setLastApiErrorDateTime( $errorMessage ? CoreHelper::now() : null );
	}

}
