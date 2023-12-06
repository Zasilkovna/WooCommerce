<?php

namespace Packetery\spec\Phpro\SoapClient\Soap\HttpBinding\Converter;

use Packetery\GuzzleHttp\Psr7\Request;
use Packetery\GuzzleHttp\Psr7\Response;
use Packetery\GuzzleHttp\Psr7\Stream;
use Packetery\Http\Message\MessageFactory;
use Packetery\Http\Message\StreamFactory;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapRequest;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\SoapResponse;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\Soap\HttpBinding\Converter\Psr7Converter;
/**
 * Class Psr7ConverterSpec
 * @internal
 */
class Psr7ConverterSpec extends ObjectBehavior
{
    function let(MessageFactory $requestFactory, StreamFactory $streamFactory)
    {
        $this->beConstructedWith($requestFactory, $streamFactory);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(Psr7Converter::class);
    }
    function it_can_create_a_request(MessageFactory $requestFactory, StreamFactory $streamFactory)
    {
        $requestFactory->createRequest('POST', '/url')->willReturn($request = new Request('POST', '/uri'));
        $streamFactory->createStream('request')->willReturn($stream = new Stream(\fopen('php://memory', 'rwb')));
        $soapRequest = new SoapRequest('request', '/url', 'action', 1, 0);
        $result = $this->convertSoapRequest($soapRequest);
        $result->shouldBeAnInstanceOf(Request::class);
        $result->getBody()->shouldBe($stream);
    }
    function it_can_create_a_response()
    {
        $stream = new Stream(\fopen('php://memory', 'rwb'));
        $stream->write('response');
        $stream->rewind();
        $response = (new Response())->withBody($stream);
        $result = $this->convertSoapResponse($response);
        $result->shouldBeAnInstanceOf(SoapResponse::class);
        $result->getResponse()->shouldBe('response');
    }
}
