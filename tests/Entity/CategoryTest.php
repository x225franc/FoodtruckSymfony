<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryEntity(): void
    {
        $category = new Category();
        $category->setTitle('Burgers');
        $category->setDescription('Delicious burgers');

        $this->assertEquals('Burgers', $category->getTitle());
        $this->assertEquals('Delicious burgers', $category->getDescription());
    }
}
