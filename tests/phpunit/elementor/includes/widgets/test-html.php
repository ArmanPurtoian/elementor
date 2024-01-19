<?php

namespace Elementor\Tests\Phpunit\Includes\Widgets;

use Elementor\Widget_Html;
use ElementorEditorTesting\Elementor_Test_Base;

class Test_HTML extends Elementor_Test_Base {

	private $widgetHtml;

	public function setUp(): void {
		$this->widgetHtml = new Widget_Html();
	}

	public function testSanitizeRedirectUrlsWithSafeContent() {
		$html = '<a href="https://example.com">Link</a>';
		$sanitizedHtml = $this->widgetHtml->sanitize_redirect_urls($html);
		$this->assertEquals($html, $sanitizedHtml);
	}

	public function testSanitizeRedirectUrlsWithJavaScript() {
		$html = '<a href="javascript:alert(\'XSS\')">Click me</a>';
		$sanitizedHtml = $this->widgetHtml->sanitize_redirect_urls($html);
		$this->assertNotEquals($html, $sanitizedHtml);
	}
}
