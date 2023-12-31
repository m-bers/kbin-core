<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\MentionManager;
use App\Tests\WebTestCase;

class MentionManagerTest extends WebTestCase
{
    /**
     * @dataProvider provider
     */
    public function testExtract(string $input, ?array $output): void
    {
        $this->createClient();

        $manager = $this->getContainer()->get(MentionManager::class);
        $this->assertEquals($output, $manager->extract($input));
    }

    public function provider(): array
    {
        // @todo federated mentions
        return [
            ['Lorem @john ipsum', ['@john']],
            ['@john lorem ipsum', ['@john']],
            ['Lorem ipsum@john', null],
        ];
    }
}
