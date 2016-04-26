<?php
namespace Wwwision\GraphQL\Tests\Unit\Fixtures;


class ExampleObject
{
    private $string;

    /**
     * ExampleObject constructor.
     * @param $string
     */
    public function __construct($string)
    {
        $this->string = $string;
    }

    public function getSomeString()
    {
        return $this->string;
    }

    public function getSomeArray()
    {
        return ['string' => $this->string, 'neos' => 'rocks'];
    }

    public function isFoo()
    {
        return true;
    }

    public function hasBar()
    {
        return false;
    }

    public function getSomeDate()
    {
        return new \DateTimeImmutable('1980-12-13');
    }

    public function getSomeSubObject()
    {
        return new self($this->string . ' nested');
    }

    public function getSomeSubObjectsArray()
    {
        return [
            new self($this->string . ' nested a'),
            new self($this->string . ' nested b')
        ];
    }

    public function getSomeSubObjectsIterator()
    {
        return new \ArrayIterator($this->getSomeSubObjectsArray());
    }
}