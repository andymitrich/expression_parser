<?php

class ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new Parser();
    }

    public function example1()
    {
        return array(
            array("offer*caps(5,2)")
        );
    }

    public function example2()
    {
        return array(
            array("model")
        );
    }

    public function example3()
    {
        return array(
            array("model*withoutSpaces,justDigits,digitToLetter,разбитьНаСлова(),specialToSpace,zi,!exclude('empty'),!include('lengthWithoutSpace>',4)")
        );
    }

    /**
     * @test
     * @dataProvider example1
     * @param $expression
     */
    public function parseExample1($expression)
    {
        $result = $this->parser->parse($expression);
        $this->assertEquals(Parser::STATUS_COMPLETE, $result->status);
        $this->assertArrayHasKey('tag', $result->data);
        $this->assertEquals('offer', $result->data['tag']);
        $this->assertArrayHasKey('options', $result->data);
        $this->assertCount(1, $result->data['options']);
        $this->assertArrayHasKey('name', $result->data['options'][0]);
        $this->assertEquals('caps', $result->data['options'][0]['name']);
        $this->assertCount(2, $result->data['options'][0]['parameters']);
    }

    /**
     * @test
     * @dataProvider example2
     * @param $expression
     */
    public function parseExample2($expression)
    {
        $result = $this->parser->parse($expression);
        $this->assertEquals(Parser::STATUS_COMPLETE, $result->status);
        $this->assertArrayHasKey('tag', $result->data);
        $this->assertEquals('model', $result->data['tag']);
        $this->assertArrayHasKey('options', $result->data);
        $this->assertCount(0, $result->data['options']);
    }

    /**
     * @test
     * @dataProvider example3
     * @param $expression
     */
    public function parseExample3($expression)
    {
        $result = $this->parser->parse($expression);
        $this->assertEquals(Parser::STATUS_COMPLETE, $result->status);
        $this->assertArrayHasKey('tag', $result->data);
        $this->assertEquals('model', $result->data['tag']);
        $this->assertArrayHasKey('options', $result->data);
        $this->assertCount(8, $result->data['options']);

        foreach ($result->data['options'] as $option) {
            $this->assertArrayHasKey('name', $option);

            if ($option['name'] == 'exclude' || $option['name'] == 'include') {
                $this->assertArrayHasKey('hasExclamation', $option);
                $this->assertEquals(1, $option['hasExclamation']);
            }
        }
    }

    /**
     * @test
     * @dataProvider example1
     */
    public function addSynonym($expression)
    {
        $this->parser->setSynonym('caps', 'notcaps');
        $result = $this->parser->parse($expression);
        $this->assertEquals(Parser::STATUS_COMPLETE, $result->status);
        $this->assertArrayHasKey('tag', $result->data);
        $this->assertEquals('offer', $result->data['tag']);
        $this->assertArrayHasKey('options', $result->data);
        $this->assertCount(1, $result->data['options']);
        $this->assertArrayHasKey('name', $result->data['options'][0]);
        $this->assertEquals('notcaps', $result->data['options'][0]['name']);
        $this->assertCount(2, $result->data['options'][0]['parameters']);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 1
     */
    public function testAddDuplicateSynonym()
    {
        $this->parser->setSynonym('caps', 'notcaps');
        $this->parser->setSynonym('caps', 'duplicate');
    }
} 