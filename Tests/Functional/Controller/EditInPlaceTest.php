<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\Bundle\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Request;
use Translation\Bundle\EditInPlace\Activator;
use Translation\Bundle\Tests\Functional\BaseTestCase;

/**
 * @author Damien Alexandre <dalexandre@jolicode.com>
 */
class EditInPlaceTest extends BaseTestCase
{
    public function testActivatedTest(): void
    {
        $this->bootKernel();
        $request = Request::create('/foobar');

        // Activate the feature
        $this->getContainer()->get(Activator::class)->activate();

        $response = $this->kernel->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<!-- TranslationBundle -->', $response->getContent());

        $dom = new \DOMDocument('1.0', 'utf-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // Check number of x-trans tags
        $xtrans = $xpath->query('//x-trans');
        self::assertEquals(6, $xtrans->length);

        // Check attribute with prefix (href="mailto:...")
        $emailTag = $dom->getElementById('email');
        self::assertEquals('mailto:'.'🚫 Can\'t be translated here. 🚫', $emailTag->getAttribute('href'));
        self::assertEquals('localized.email', $emailTag->textContent);

        // Check attribute
        $attributeDiv = $dom->getElementById('attribute-div');
        self::assertEquals('🚫 Can\'t be translated here. 🚫', $attributeDiv->getAttribute('data-value'));
    }

    public function testIfUntranslatableLabelGetsDisabled(): void
    {
        $this->kernel->addConfigFile(__DIR__.'/../app/config/disabled_label.yaml');
        $request = Request::create('/foobar');

        // Activate the feature
        $this->bootKernel();
        $this->getContainer()->get(Activator::class)->activate();

        $response = $this->kernel->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<!-- TranslationBundle -->', $response->getContent());

        $dom = new \DOMDocument('1.0', 'utf-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // Check number of x-trans tags
        $xtrans = $xpath->query('//x-trans');
        self::assertEquals(6, $xtrans->length);

        // Check attribute with prefix (href="mailto:...")
        $emailTag = $dom->getElementById('email');
        self::assertEquals('localized.email', $emailTag->getAttribute('href'));
        self::assertEquals('localized.email', $emailTag->textContent);

        // Check attribute
        $attributeDiv = $dom->getElementById('attribute-div');
        self::assertEquals('translated.attribute', $attributeDiv->getAttribute('data-value'));
    }

    public function testDeactivatedTest(): void
    {
        $this->bootKernel();
        $request = Request::create('/foobar');
        $response = $this->kernel->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringNotContainsString('x-trans', $response->getContent());
    }
}
