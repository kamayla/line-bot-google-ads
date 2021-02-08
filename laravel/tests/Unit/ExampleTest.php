<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        factory(Book::class, 1000)->create();
        $this->assertCount(1000, Book::all());
    }


    public function testFailed()
    {
        $this->assertSame(3, 3);
    }
}
