<?php

namespace Packetery\spec\Phpro\SoapClient\Soap\HttpBinding;

use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapResponse;
/**
 * Class SoapResponseSpec
 * @internal
 */
class SoapResponseSpec extends ObjectBehavior
{
    private $response = '<soap:Envelope />';
    function let()
    {
        $this->beConstructedWith($this->response);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(SoapResponse::class);
    }
    function it_contains_the_response()
    {
        $this->getResponse()->shouldBe($this->response);
    }
}
