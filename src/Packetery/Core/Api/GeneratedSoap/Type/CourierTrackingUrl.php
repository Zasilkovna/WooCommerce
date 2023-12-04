<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class CourierTrackingUrl
{

    /**
     * @var string
     */
    private $lang;

    /**
     * @var string
     */
    private $url;

    public function getLang()
    {
        return $this->lang;
    }

    public function withLang($lang)
    {
        $new = clone $this;
        $new->lang = $lang;

        return $new;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function withUrl($url)
    {
        $new = clone $this;
        $new->url = $url;

        return $new;
    }


}

