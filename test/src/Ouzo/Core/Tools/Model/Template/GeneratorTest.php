<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */

namespace Ouzo\Tools\Model\Template;

use Ouzo\Config;
use Ouzo\Db\Dialect\Dialect;
use Ouzo\Tests\Assert;
use Ouzo\Tests\CatchException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class GeneratorTest extends TestCase
{
    public function setUp()
    {
        $driver = Config::getValue('db', 'driver');
        if ($driver == 'sqlite') {
            $this->markTestSkipped('This test is not for SQLite database.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function shouldReturnObjectForConfiguredDialect()
    {
        //given
        $generator = new Generator('products');

        //when
        $templateDialect = $generator->dialectAdapter();

        //then
        $configuredDialectClassPath = Config::getValue('sql_dialect');
        $dialectReflectionClass = new ReflectionClass($templateDialect);
        $generatorDialectClassName = $dialectReflectionClass->getShortName();
        $this->assertStringEndsWith($generatorDialectClassName, $configuredDialectClassPath);
    }

    /**
     * @test
     */
    public function shouldRemoveTablePrefixFromClass()
    {
        //given
        $generator = new Generator('t_my_table');

        //when
        $modelName = $generator->getTemplateClassName();

        //then
        $this->assertEquals('MyTable', $modelName);
    }

    /**
     * @test
     */
    public function shouldSingularizeTableName()
    {
        //given
        $generator = new Generator('order_products');

        //when
        $modelName = $generator->getTemplateClassName();

        //then
        $this->assertEquals('OrderProduct', $modelName);
    }

    /**
     * @test
     */
    public function shouldReturnTableInformation()
    {
        //given
        $generator = new Generator('products');

        //when
        $dialectAdapter = $generator->dialectAdapter();

        //then
        $this->assertEquals('id', $dialectAdapter->primaryKey());
        Assert::thatArray($dialectAdapter->columns())
            ->onProperty('name')
            ->containsOnly('id', 'id_category', 'name', 'description', 'sale', 'id_manufacturer');
        Assert::thatArray($dialectAdapter->columns())->onProperty('type')->contains('string', 'string', 'int', 'int');
    }

    /**
     * @test
     * @throws \Exception
     */
    public function shouldThrowExceptionWhenDialectAdapterNotExists()
    {
        //given
        Config::overrideProperty('sql_dialect')->with(MyImagineDialect::class);

        //when
        CatchException::inConstructor(Generator::class, ['order_products']);

        //then
        CatchException::assertThat()->isInstanceOf(GeneratorException::class);
        Config::revertProperty('sql_dialect');
    }

    /**
     * @test
     */
    public function shouldSaveToFile()
    {
        //given
        $generator = new Generator('products');
        $fileName = '/tmp/example.php';

        //when
        $generator->saveToFile($fileName);

        //then
        $this->assertFileExists($fileName);
        unlink($fileName);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenFileAlreadyExists()
    {
        //given
        $generator = new Generator('products');
        $fileName = '/tmp/example.php';
        file_put_contents($fileName, '');

        //when
        CatchException::when($generator)->saveToFile($fileName);

        //then
        CatchException::assertThat()->isInstanceOf(GeneratorException::class);
        unlink($fileName);
    }

    /**
     * @test
     */
    public function shouldPrepareTemplateContents()
    {
        //given
        $generator = new Generator('products');

        //when
        $template = $generator->templateContents();

        //then
        $this->assertContains('class Product extends Model', $template);
        $this->assertContains('string description', $template);
    }

    /**
     * @test
     */
    public function shouldGetCorrectNamespace()
    {
        //given
        $generator = new Generator('products');

        //when
        $namespace = $generator->getClassNamespace();

        //then
        $this->assertEquals('Application\Model', $namespace);
    }
}

/**
 * @SuppressWarnings(PHPMD)
 */
class MyImagineDialect extends Dialect
{
    public function getConnectionErrorCodes()
    {
        return [];
    }

    public function getErrorCode($errorInfo)
    {
        return 0;
    }

    function batchInsert($table, $primaryKey, $columns, $batchSize)
    {
    }

    protected function insertEmptyRow()
    {
        return '';
    }

    public function regexpMatcher()
    {
        return '~';
    }

    protected function quote($word)
    {
        return $word;
    }

    public function onConflictUpdate()
    {
        return '';
    }

    public function onConflictDoNothing()
    {
        return '';
    }
}
