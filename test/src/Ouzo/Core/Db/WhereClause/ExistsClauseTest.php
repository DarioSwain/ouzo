<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo\Db\WhereClause;


use Application\Model\Test\Product;
use Ouzo\Tests\Assert;

class ExistsClauseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBuildExistsClause()
    {
        // when
        $result = WhereClause::exists(Product::where(['name' => 'phone']));

        // then
        $this->assertEquals(['phone'], $result->getParameters());
        Assert::thatString($result->toSql())
            ->startsWith('EXISTS (SELECT')
            ->endsWith('FROM products WHERE name = ?)');
    }

    /**
     * @test
     */
    public function shouldBuildNotExistsClause()
    {
        // when
        $result = WhereClause::notExists(Product::where(['name' => 'phone']));

        // then
        $this->assertEquals(['phone'], $result->getParameters());
        Assert::thatString($result->toSql())
            ->startsWith('NOT EXISTS (SELECT')
            ->endsWith('FROM products WHERE name = ?)');
    }
}
