<?php
/**
 * Class Packet.
 *
 * @package Packetery\Api\Soap
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap;

use SoapClient;
use SoapFault;

/**
 * Class Packet.
 *
 * @package Packetery\Api\Soap
 */
class Client {

	private const WSDL_URL = 'http://www.zasilkovna.cz/api/soap-php-bugfix.wsdl';

	/**
	 * API password.
	 *
	 * @var string|null
	 */
	private $apiPassword;

	/**
	 * Client constructor.
	 *
	 * @param string|null $apiPassword Api password.
	 */
	public function __construct( ?string $apiPassword ) {
		$this->apiPassword = $apiPassword;
	}

	/**
	 * Sets API password.
	 *
	 * @param string $apiPassword API password.
	 *
	 * @return void
	 */
	public function setApiPassword( string $apiPassword ): void {
		$this->apiPassword = $apiPassword;
	}

	/**
	 * Creates storage file.
	 *
	 * @param Request\CreateStorageFile $request Request.
	 * @return Response\CreateStorageFile
	 */
	public function createStorageFile( Request\CreateStorageFile $request ): Response\CreateStorageFile {
		$response = new Response\CreateStorageFile();
		try {
			$soapClient  = new SoapClient( self::WSDL_URL );
			$storageFile = $soapClient->createStorageFile(
				$this->apiPassword,
				[
					'content' => $request->getContent(),
					'name'    => $request->getName(),
				]
			);
			$response->setId( (string) $storageFile->id );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Submits packet data to Packeta API.
	 * We deliberately don't use Request\CreatePacket class to send data to the API, but we keep the class for possible later use.
	 *
	 * @param array<string, mixed> $requestData Packet attributes.
	 *
	 * @return Response\CreatePacket
	 */
	public function createPacket( array $requestData ): Response\CreatePacket {
		$response = new Response\CreatePacket();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$packet     = $soapClient->createPacket( $this->apiPassword, $requestData );
			$response->setId( $packet->id );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );
			$response->setValidationErrors( $this->getValidationErrors( $exception ) );
		}

		return $response;
	}

	/**
	 * Create claim packet with password.
	 *
	 * @param Request\CreatePacketClaimWithPassword $request Request attributes.
	 *
	 * @return Response\CreatePacketClaimWithPassword
	 */
	public function createPacketClaimWithPassword( Request\CreatePacketClaimWithPassword $request ): Response\CreatePacketClaimWithPassword {
		$response = new Response\CreatePacketClaimWithPassword();
		try {
			$soapClient              = new SoapClient( self::WSDL_URL );
			$packetClaimWithPassword = $soapClient->createPacketClaimWithPassword( $this->apiPassword, $request->getSubmittableData() );
			$response->setId( $packetClaimWithPassword->id );
			$response->setPassword( $packetClaimWithPassword->password );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );
			$response->setValidationErrors( $this->getValidationErrors( $exception ) );
		}

		return $response;
	}

	/**
	 * Submits packet data to Packeta API.
	 *
	 * @param Request\CancelPacket $request Packet attributes.
	 *
	 * @return Response\CancelPacket
	 */
	public function cancelPacket( Request\CancelPacket $request ): Response\CancelPacket {
		$response = new Response\CancelPacket();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$soapClient->cancelPacket( $this->apiPassword, $request->getPacketId() );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Retrieves packet status.
	 *
	 * @param Request\PacketStatus $request Packet attributes.
	 *
	 * @return Response\PacketStatus
	 */
	public function packetStatus( Request\PacketStatus $request ): Response\PacketStatus {
		$response = new Response\PacketStatus();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$result     = $soapClient->packetStatus( $this->apiPassword, $request->getPacketId() );
			$response->setCodeText( $result->codeText );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Create shipment.
	 *
	 * @param Request\CreateShipment $request Request.
	 *
	 * @return Response\CreateShipment
	 */
	public function createShipment( Request\CreateShipment $request ): Response\CreateShipment {
		$response = new Response\CreateShipment();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$shipment   = $soapClient->createShipment( $this->apiPassword, $request->getPacketIds(), $request->getCustomBarcode() );
			$response->setId( (string) $shipment->id );
			$response->setChecksum( $shipment->checksum );
			$response->setBarcode( $shipment->barcode );
			$response->setBarcodeText( $shipment->barcodeText );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );

			if ( isset( $exception->detail, $exception->detail->PacketIdsFault ) ) {
				$invalidPacketIds         = (array) $exception->detail->PacketIdsFault->ids->packetId;
				$invalidPacketIdsFiltered = [];

				foreach ( $invalidPacketIds as $invalidPacketId ) {
					if ( empty( $invalidPacketId ) ) {
						continue;
					}

					$invalidPacketIdsFiltered[] = $invalidPacketId;
				}

				$response->setInvalidPacketIds( $invalidPacketIdsFiltered );
			}
		}

		return $response;
	}

	/**
	 * Barcode PNG.
	 *
	 * @param Request\BarcodePng $request Request.
	 *
	 * @return Response\BarcodePng
	 */
	public function barcodePng( Request\BarcodePng $request ): Response\BarcodePng {
		$response = new Response\BarcodePng();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$data       = $soapClient->barcodePng( $this->apiPassword, $request->getBarcode() );
			$response->setImageContent( $data );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Asks for Packeta labels.
	 *
	 * @param Request\PacketsLabelsPdf $request Label request.
	 *
	 * @return Response\PacketsLabelsPdf
	 */
	public function packetsLabelsPdf( Request\PacketsLabelsPdf $request ): Response\PacketsLabelsPdf {
		$response = new Response\PacketsLabelsPdf();
		try {
			$soapClient  = new SoapClient( self::WSDL_URL );
			$pdfContents = $soapClient->packetsLabelsPdf( $this->apiPassword, $request->getPacketIds(), $request->getFormat(), $request->getOffset() );
			$response->setPdfContents( $pdfContents );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );

			if ( $response->hasPacketIdsFault() ) {
				$response->setInvalidPacketIds( (array) $exception->detail->PacketIdsFault->ids->packetId );
			}
		}

		return $response;
	}

	/**
	 * Asks for carrier labels.
	 *
	 * @param Request\PacketsCourierLabelsPdf $request Label request.
	 *
	 * @return Response\PacketsCourierLabelsPdf
	 */
	public function packetsCarrierLabelsPdf( Request\PacketsCourierLabelsPdf $request ): Response\PacketsCourierLabelsPdf {
		$response = new Response\PacketsCourierLabelsPdf();
		try {
			$soapClient  = new SoapClient( self::WSDL_URL );
			$pdfContents = $soapClient->packetsCourierLabelsPdf( $this->apiPassword, $request->getPacketIdsWithCourierNumbers(), $request->getOffset(), $request->getFormat() );
			$response->setPdfContents( $pdfContents );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );

			if ( $response->hasInvalidCourierNumberFault() && count( $request->getPacketIdsWithCourierNumbers() ) === 1 ) {
				$response->setInvalidCourierNumbers( array_column( $request->getPacketIdsWithCourierNumbers(), 'courierNumber' ) );
			}
			if ( $response->hasPacketIdFault() && count( $request->getPacketIdsWithCourierNumbers() ) === 1 ) {
				$response->setInvalidPacketIds( array_column( $request->getPacketIdsWithCourierNumbers(), 'packetId' ) );
			}
		}

		return $response;
	}

	/**
	 * Requests carrier number for a packet.
	 *
	 * @param Request\PacketCourierNumber $request PacketCourierNumber request.
	 *
	 * @return Response\PacketCourierNumber
	 */
	public function packetCourierNumber( Request\PacketCourierNumber $request ): Response\PacketCourierNumber {
		$response = new Response\PacketCourierNumber();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$number     = $soapClient->packetCourierNumber( $this->apiPassword, $request->getPacketId() );
			$response->setNumber( $number );
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Requests for sender return routing strings.
	 *
	 * @param Request\SenderGetReturnRouting $request Request.
	 *
	 * @return Response\SenderGetReturnRouting
	 */
	public function senderGetReturnRouting( Request\SenderGetReturnRouting $request ): Response\SenderGetReturnRouting {
		$response = new Response\SenderGetReturnRouting();
		try {
			$soapClient = new SoapClient( self::WSDL_URL );
			$soapClient->senderGetReturnRouting( $this->apiPassword, $request->getSenderLabel() );
			// TODO: Set return routing strings.
		} catch ( SoapFault $exception ) {
			$response->setFault( $this->getFaultIdentifier( $exception ) );
			$response->setFaultString( $exception->faultstring );
		}

		return $response;
	}

	/**
	 * Gets human-readable errors from SoapFault exception.
	 *
	 * @param SoapFault $exception Exception.
	 *
	 * @return string[]
	 */
	protected function getValidationErrors( SoapFault $exception ): array {
		$errors = [];

		$faults = ( $exception->detail->PacketAttributesFault->attributes->fault ?? [] );
		if ( $faults && ! is_array( $faults ) ) {
			$faults = [ $faults ];
		}
		if ( is_iterable( $faults ) ) {
			foreach ( $faults as $fault ) {
				$errors[] = sprintf( '%s: %s', $fault->name, $fault->fault );
			}
		}

		return $errors;
	}

	/**
	 * Gets fault identifier from SoapFault exception.
	 *
	 * @param SoapFault $exception Exception.
	 *
	 * @return string
	 */
	private function getFaultIdentifier( SoapFault $exception ): string {
		if ( isset( $exception->detail ) ) {
			return (string) array_keys( get_object_vars( $exception->detail ) )[0];
		}

		return $exception->faultstring;
	}
}
