<?php

namespace Packetery\spec\Phpro\SoapClient\Soap\HttpBinding;

use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\LastRequestInfo;
/**
 * Class LastRequestInfoSpec
 * @internal
 */
class LastRequestInfoSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('requestheaders', 'request', 'responseheaders', 'response');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(LastRequestInfo::class);
    }
    function it_contains_the_request_headers()
    {
        $this->getLastRequestHeaders()->shouldBe('requestheaders');
    }
    function it_contains_the_request()
    {
        $this->getLastRequest()->shouldBe('request');
    }
    function it_contains_the_response_headers()
    {
        $this->getLastResponseHeaders()->shouldBe('responseheaders');
    }
    function it_contains_the_response()
    {
        $this->getLastResponse()->shouldBe('response');
    }
    function it_can_create_an_empty_class()
    {
        $result = $this->createEmpty();
        $result->shouldBeAnInstanceOf(LastRequestInfo::class);
        $result->getLastRequestHeaders()->shouldBe('');
        $result->getLastRequest()->shouldBe('');
        $result->getLastResponseHeaders()->shouldBe('');
        $result->getLastResponse()->shouldBe('');
    }
}
