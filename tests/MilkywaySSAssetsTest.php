<?php

use Milkyway\SS\Assets;

/**
 * Tests {@see Milkyway\SS\Assets}
 */

class MilkywaySSAssetsTest extends \SapphireTest {
	public function testAddAndRemoveRequirements() {
		$requirements = [
			'jquery.js',
			'bootstrap.css',
		];

		$assets = new Assets();
		$assets->add($requirements);

		// Check if javascript files added as a first file
		$this->assertArrayHasKey('jquery.js', $assets->get_files_by_type('js', 'first'));

		$assets->add($requirements, 'last');

		// Check if css files added as a last file
		$this->assertArrayHasKey('bootstrap.css', $assets->get_files_by_type('css', 'last'));

		$assets->add('before_bootstrap.css', 'last', 'bootstrap.css');

		// Check if css file was added before bootstrap.css
		$css = $assets->get_files_by_type('css', 'last');
		$bootstrap = $beforeBootstrap = 0;

		$count = 0;
		foreach($css as $file => $details) {
			$count++;

			if($file == 'bootstrap.css')
				$bootstrap = $count;
			elseif($file == 'before_bootstrap.css')
				$beforeBootstrap = $count;
		}

		$this->assertTrue($beforeBootstrap && ($beforeBootstrap < $bootstrap), 'Check if asset can be inserted before other asset');

		$assets->remove('before_bootstrap.css');

		// Check if before_bootstrap.css removed
		$this->assertArrayNotHasKey('before_bootstrap.css', $assets->get_files_by_type('css', 'last'));
	}

//	public function testRequirementsAreLoadedInHtml() {
//		$html = '
//			<html>
//				<head></head>
//				<body></body>
//			</html>
//		';
//
//		$requirements = [
//			'jquery.js',
//			'http://google.com/bootstrap.css',
//		];
//
//		$assets = new Assets();
//		$assets->add($requirements);
//
//		$backend = new \Milkyway\SS\Assets_Backend();
//
//		$html = $backend->includeInHTML(false, $html);
//	}
} 