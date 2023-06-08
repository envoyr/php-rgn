<?php

/*
 * This file is part of the Rgn package.
 *
 * Copyright (c) 2023 Robin van der Vleuten <robin@webstronauts.co>
 * Copyright (c) 2023 envoyr <hello@envoyr.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Envoyr\Rgn\Tests;

use Envoyr\Rgn\Rgn;
use PHPUnit\Framework\TestCase;

/**
 * @group time-sensitive
 */
final class RgnTest extends TestCase
{
    public function testGenerateWithIdentifier(): void
    {
        $rgn = Rgn::generate(404);

        $this->assertSame('CM', $rgn->getIdentifier());
        $this->assertSame(404, $rgn->toIdentifierInt());
        $this->assertRegExp('/[0-9][A-Z]/', (string) $rgn);
        $this->assertFalse($rgn->isLowercase());
    }

    public function testGenerateWithTimestampAndIdentifier(): void
    {
        $rgn = Rgn::fromTimestamp('1686221614164', 404);

        $this->assertSame(1686221614164, $rgn->toTimestamp());
        $this->assertSame(404, $rgn->toIdentifierInt());
        $this->assertRegExp('/[0-9][A-Z]/', (string) $rgn);
        $this->assertFalse($rgn->isLowercase());
    }

    public function testGenerateFromString(): void
    {
        $rgn = Rgn::fromString('01H2DA7V2MCM');

        $this->assertSame(1686221614164, $rgn->toTimestamp());
        $this->assertSame(404, $rgn->toIdentifierInt());
        $this->assertRegExp('/[0-9][A-Z]/', (string) $rgn);
        $this->assertFalse($rgn->isLowercase());
    }
}