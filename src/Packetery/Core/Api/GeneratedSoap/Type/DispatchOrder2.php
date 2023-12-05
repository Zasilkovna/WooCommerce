<?php

namespace Packetery\Core\Api\GeneratedSoap\Type;

class DispatchOrder2
{

    /**
     * @var string
     */
    private $goods;

    /**
     * @var string
     */
    private $pdf;

    public function getGoods()
    {
        return $this->goods;
    }

    public function withGoods($goods)
    {
        $new = clone $this;
        $new->goods = $goods;

        return $new;
    }

    public function getPdf()
    {
        return $this->pdf;
    }

    public function withPdf($pdf)
    {
        $new = clone $this;
        $new->pdf = $pdf;

        return $new;
    }


}

