<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */

namespace Some\Test\Ns;

use Ouzo\Utilities\ToString\ToStringBuilder;
use Ouzo\Utilities\ToString\ToStringStyle;
use PHPUnit\Framework\TestCase;

class ToStringBuilderClass
{
    /** @var string */
    private $name;
    /** @var int */
    private $age;
    /** @var boolean */
    private $smoking;
    /** @var array */
    private $tags;
    /** @var array */
    private $customFields;
    /** @var string|null */
    private $nullable;
    /** @var object */
    private $classWithoutToString;
    /** @var object */
    private $classWithToString;

    /** @var ToStringStyle */
    private $style = null;

    public function __construct($name, $age, $smoking, $tags, $customFields, $nullable, $classWithoutToString, $classWithToString)
    {
        $this->name = $name;
        $this->age = $age;
        $this->smoking = $smoking;
        $this->tags = $tags;
        $this->customFields = $customFields;
        $this->nullable = $nullable;
        $this->classWithoutToString = $classWithoutToString;
        $this->classWithToString = $classWithToString;
    }

    public function setStyle(ToStringStyle $style)
    {
        $this->style = $style;
    }

    public function __toString()
    {
        return (new ToStringBuilder($this, $this->style))
            ->append('name', $this->name)
            ->append('age', $this->age)
            ->append('smoking', $this->smoking)
            ->append('tags', $this->tags)
            ->append('customFields', $this->customFields)
            ->append('nullable', $this->nullable)
            ->append('classWithoutToString', $this->classWithoutToString)
            ->append('classWithToString', $this->classWithToString)
            ->toString();
    }
}

class ClassWithoutToString
{
    private $string;

    public function __construct($string)
    {
        $this->string = $string;
    }
}

class ClassWithToString
{
    private $string;

    public function __construct($string)
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return (new ToStringBuilder($this))
            ->append('string', $this->string)
            ->toString();
    }
}

class ToStringBuilderTest extends TestCase
{
    /** @var ToStringBuilderClass */
    private $toStringBuilderClass;

    public function setUp()
    {
        parent::setUp();

        $string = "jon";
        $int = 91;
        $boolean = true;
        $array = ['tag1', 'tag2', 'another tag'];
        $map = ['field1' => 'value1', 'field2' => 'value2'];
        $nullable = null;
        $classWithoutToString = new ClassWithoutToString("some string");
        $classWithToString = new ClassWithToString("some new string");

        $this->toStringBuilderClass = new ToStringBuilderClass($string, $int, $boolean, $array, $map, $nullable, $classWithoutToString, $classWithToString);
    }

    /**
     * @test
     */
    public function shouldUseDefaultStyle()
    {
        //when
        $toString = $this->toStringBuilderClass->__toString();

        //then
        $expected = 'Some\Test\Ns\ToStringBuilderClass[name=jon,age=91,smoking=true,tags={tag1,tag2,another tag},customFields={field1=value1,field2=value2},nullable=<null>,classWithoutToString=Some\Test\Ns\ClassWithoutToString,classWithToString=Some\Test\Ns\ClassWithToString[string=some new string]]';
        $this->assertEquals($expected, $toString);
    }

    /**
     * @test
     */
    public function shouldUseNoFieldNameStyle()
    {
        //given
        $this->toStringBuilderClass->setStyle(ToStringStyle::noFieldNamesStyle());

        //when
        $toString = $this->toStringBuilderClass->__toString();

        //then
        $expected = 'Some\Test\Ns\ToStringBuilderClass[jon,91,true,{tag1,tag2,another tag},{field1=value1,field2=value2},<null>,Some\Test\Ns\ClassWithoutToString,Some\Test\Ns\ClassWithToString[string=some new string]]';
        $this->assertEquals($expected, $toString);
    }

    /**
     * @test
     */
    public function shouldUseShortPrefixStyle()
    {
        //given
        $this->toStringBuilderClass->setStyle(ToStringStyle::shortPrefixStyle());

        //when
        $toString = $this->toStringBuilderClass->__toString();

        //then
        $expected = 'ToStringBuilderClass[name=jon,age=91,smoking=true,tags={tag1,tag2,another tag},customFields={field1=value1,field2=value2},nullable=<null>,classWithoutToString=Some\Test\Ns\ClassWithoutToString,classWithToString=Some\Test\Ns\ClassWithToString[string=some new string]]';
        $this->assertEquals($expected, $toString);
    }

    /**
     * @test
     */
    public function shouldUseSimpleStyle()
    {
        //given
        $this->toStringBuilderClass->setStyle(ToStringStyle::simpleStyle());

        //when
        $toString = $this->toStringBuilderClass->__toString();

        //then
        $expected = 'jon,91,true,{tag1,tag2,another tag},{field1=value1,field2=value2},<null>,Some\Test\Ns\ClassWithoutToString,Some\Test\Ns\ClassWithToString[string=some new string]';
        $this->assertEquals($expected, $toString);
    }

    /**
     * @test
     */
    public function shouldUseNoClassNameStyle()
    {
        //given
        $this->toStringBuilderClass->setStyle(ToStringStyle::noClassNameStyle());

        //when
        $toString = $this->toStringBuilderClass->__toString();

        //then
        $expected = '[name=jon,age=91,smoking=true,tags={tag1,tag2,another tag},customFields={field1=value1,field2=value2},nullable=<null>,classWithoutToString=Some\Test\Ns\ClassWithoutToString,classWithToString=Some\Test\Ns\ClassWithToString[string=some new string]]';
        $this->assertEquals($expected, $toString);
    }

    /**
     * @test
     */
    public function shouldUseMultiLineStyle()
    {
        //given
        $this->toStringBuilderClass->setStyle(ToStringStyle::multiLineStyle());

        //when
        $toString = $this->toStringBuilderClass->__toString();

        //then
        $expected = 'Some\Test\Ns\ToStringBuilderClass[
  name=jon
  age=91
  smoking=true
  tags={tag1,tag2,another tag}
  customFields={field1=value1,field2=value2}
  nullable=<null>
  classWithoutToString=Some\Test\Ns\ClassWithoutToString
  classWithToString=Some\Test\Ns\ClassWithToString[string=some new string]
]';
        $this->assertEquals($expected, $toString);
    }
}
