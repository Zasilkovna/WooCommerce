<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class Security
{

    /**
     * @var bool
     */
    private $allowPublicTracking;

    /**
     * @var \Packetery\Core\Api\GeneratedSoap\Type\AllowTrackingForUsers
     */
    private $allowTrackingForUsers;

    public function getAllowPublicTracking()
    {
        return $this->allowPublicTracking;
    }

    public function withAllowPublicTracking($allowPublicTracking)
    {
        $new = clone $this;
        $new->allowPublicTracking = $allowPublicTracking;

        return $new;
    }

    public function getAllowTrackingForUsers()
    {
        return $this->allowTrackingForUsers;
    }

    public function withAllowTrackingForUsers($allowTrackingForUsers)
    {
        $new = clone $this;
        $new->allowTrackingForUsers = $allowTrackingForUsers;

        return $new;
    }


}

