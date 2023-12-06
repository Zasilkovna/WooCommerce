<?php

namespace Packetery\Core\Api\GeneratedSoap;

use Packetery\Core\Api\GeneratedSoap\Type;
use Packetery\Phpro\SoapClient\Type\ResultInterface;

class PacketerySoapClient extends \Packetery\Phpro\SoapClient\Client
{

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\PacketAttributes $attributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function packetAttributesValid(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('packetAttributesValid', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\ClaimAttributes $claimAttributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function packetClaimAttributesValid(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('packetClaimAttributesValid', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\PacketAttributes $attributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\PacketIdDetail
     */
    public function createPacket(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\PacketIdDetail
    {
        return $this->call('createPacket', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function cancelPacket(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('cancelPacket', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\PacketsAttributes $packets
     * Packetery\Core\Api\GeneratedSoap\Type\Boolean $transaction
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\CreatePacketsResults
     */
    public function createPackets(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\CreatePacketsResults
    {
        return $this->call('createPackets', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\PacketB2BAttributes $packetB2BAttributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\CreatePacketsB2BResults
     */
    public function createPacketsB2B(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\CreatePacketsB2BResults
    {
        return $this->call('createPacketsB2B', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\ClaimAttributes $claimAttributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\PacketIdDetail
     */
    public function createPacketClaim(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\PacketIdDetail
    {
        return $this->call('createPacketClaim', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\ClaimWithPasswordAttributes $claimWithPasswordAttributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\PacketDetail
     */
    public function createPacketClaimWithPassword(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\PacketDetail
    {
        return $this->call('createPacketClaimWithPassword', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\PacketIds $packetIds
     * string $customBarcode
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\ShipmentIdDetail
     */
    public function createShipment(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\ShipmentIdDetail
    {
        return $this->call('createShipment', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $shipmentId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\ShipmentPacketsResult
     */
    public function shipmentPackets(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\ShipmentPacketsResult
    {
        return $this->call('shipmentPackets', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\CurrentStatusRecord
     */
    public function packetStatus(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\CurrentStatusRecord
    {
        return $this->call('packetStatus', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StatusRecords
     */
    public function packetTracking(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StatusRecords
    {
        return $this->call('packetTracking', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\ExternalStatusRecords
     */
    public function packetCourierTracking(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\ExternalStatusRecords
    {
        return $this->call('packetCourierTracking', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\NullableDate
     */
    public function packetGetStoredUntil(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\NullableDate
    {
        return $this->call('packetGetStoredUntil', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * Packetery\Core\Api\GeneratedSoap\Type\Date $date
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function packetSetStoredUntil(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('packetSetStoredUntil', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $barcode
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function barcodePng(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('barcodePng', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * string $format
     * Packetery\Core\Api\GeneratedSoap\Type\Integer $offset
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetLabelPdf(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetLabelPdf', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\PacketIds $packetIds
     * string $format
     * Packetery\Core\Api\GeneratedSoap\Type\Integer $offset
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetsLabelsPdf(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetsLabelsPdf', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * Packetery\Core\Api\GeneratedSoap\Type\Integer $dpi
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetLabelZpl(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetLabelZpl', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $country
     * string $status
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function setCountryStatus(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('setCountryStatus', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\Integer $id
     * string $status
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function setBranchStatus(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('setBranchStatus', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetCourierNumber(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetCourierNumber', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\PacketCourierNumberV2Result
     */
    public function packetCourierNumberV2(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\PacketCourierNumberV2Result
    {
        return $this->call('packetCourierNumberV2', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * string $courierNumber
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetCourierBarcode(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetCourierBarcode', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * string $courierNumber
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetCourierLabelPng(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetCourierLabelPng', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * string $courierNumber
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetCourierLabelPdf(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetCourierLabelPdf', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\PacketIdsWithCourierNumbers $packetIdsWithCourierNumbers
     * Packetery\Core\Api\GeneratedSoap\Type\Integer $offset
     * string $format
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetsCourierLabelsPdf(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetsCourierLabelsPdf', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * string $courierNumber
     * Packetery\Core\Api\GeneratedSoap\Type\Integer $dpi
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function packetCourierLabelZpl(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('packetCourierLabelZpl', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * string $courierNumber
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function packetCourierConfirm(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('packetCourierConfirm', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $sender
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StringType
     */
    public function senderGetReturnString(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StringType
    {
        return $this->call('senderGetReturnString', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $sender
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\SenderGetReturnRoutingResult
     */
    public function senderGetReturnRouting(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\SenderGetReturnRoutingResult
    {
        return $this->call('senderGetReturnRouting', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $email
     * string $phone
     * Packetery\Core\Api\GeneratedSoap\Type\Integer $addressId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function adviseBranch(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('adviseBranch', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\FloatType
     */
    public function packetCod(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\FloatType
    {
        return $this->call('packetCod', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     * Packetery\Core\Api\GeneratedSoap\Type\UpdatePacketAttributes $updateAttributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\VoidType
     */
    public function updatePacket(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\VoidType
    {
        return $this->call('updatePacket', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\PacketConsignerAttributes $packetConsignerAttributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\ConsignmentPasswordResult
     */
    public function getConsignmentPassword(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\ConsignmentPasswordResult
    {
        return $this->call('getConsignmentPassword', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\StorageFileAttributes $storageFileAttributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StorageFile
     */
    public function createStorageFile(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StorageFile
    {
        return $this->call('createStorageFile', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * Packetery\Core\Api\GeneratedSoap\Type\ListStorageFileAttributes $listStorageFileAttributes
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\StorageFiles
     */
    public function listStorageFile(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\StorageFiles
    {
        return $this->call('listStorageFile', $multiArgumentRequest);
    }

    /**
     * MultiArgumentRequest with following params:
     *
     * string $apiPassword
     * string $packetId
     *
     * @param Packetery\Phpro\SoapClient\Type\MultiArgumentRequest
     * @return ResultInterface|Type\PacketInfoResult
     */
    public function packetInfo(\Packetery\Phpro\SoapClient\Type\MultiArgumentRequest $multiArgumentRequest) : \Packetery\Core\Api\GeneratedSoap\Type\PacketInfoResult
    {
        return $this->call('packetInfo', $multiArgumentRequest);
    }


}

