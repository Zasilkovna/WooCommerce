<?php

// sample data derived from
// https://github.com/woocommerce/woocommerce/blob/08894a09bb88646bda313cf826fcda6a058202a6/plugins/woocommerce/sample-data/sample_products.csv
$csvFile        = __DIR__ . '/sample_products.csv';
$categoriesFile = __DIR__ . '/categories.tsv';

$parserType = $argv[1];
$wpUser     = $argv[2];

// https://developer.woocommerce.com/docs/woocommerce-cli-commands/#wc-product-create
$mapping = [
	'name'                  => 'Name', // Product name.
	'slug'                  => 'SKU', // Product slug.
	'type'                  => 'Type', // Product type.
	'status'                => '', // Product status (post status).
	'featured'              => 'Is featured?', // Featured product.
	'catalog_visibility'    => 'Visibility in catalog', // Catalog visibility.
	'description'           => 'Description', // Product description.
	'short_description'     => 'Short description', // Product short description.
	'sku'                   => 'SKU', // Unique identifier.
	'regular_price'         => 'Regular price', // Product regular price.
	'sale_price'            => 'Sale price', // Product sale price.
	'date_on_sale_from'     => 'Date sale price starts', // Start date of sale price, in the site’s timezone.
	'date_on_sale_from_gmt' => '', // Start date of sale price, as GMT.
	'date_on_sale_to'       => 'Date sale price ends', // End date of sale price, in the site’s timezone.
	'date_on_sale_to_gmt'   => '', // End date of sale price, in the site’s timezone.
	'virtual'               => '', // If the product is virtual.
	'downloadable'          => '', // If the product is downloadable.
	'downloads'             => '', // List of downloadable files.
	'download_limit'        => 'Download limit', // Number of times downloadable files can be downloaded after purchase.
	'download_expiry'       => 'Download expiry days', // Number of days until access to downloadable files expires.
	'external_url'          => 'External URL', // Product external URL. Only for external products.
	'button_text'           => 'Button text', // Product external button text. Only for external products.
	'tax_status'            => 'Tax status', // Tax status.
	'tax_class'             => 'Tax class', // Tax class.
	'manage_stock'          => '', // Stock management at product level.
	'stock_quantity'        => 'Stock', // Stock quantity.
	'in_stock'              => 'In stock?', // Controls whether or not the product is listed as “in stock” or “out of stock” on the frontend.
	'backorders'            => 'Backorders allowed?', // If managing stock, this controls if backorders are allowed.
	'sold_individually'     => 'Sold individually?', // Allow one item to be bought in a single order.
	'weight'                => 'Weight (kgs)', // Product weight (lbs).
	'dimensions'            => '', // Product dimensions.
	'shipping_class'        => 'Shipping class', // Shipping class slug.
	'reviews_allowed'       => 'Allow customer reviews?', // Allow reviews.
	'upsell_ids'            => 'Upsells', // List of up-sell products IDs.
	'cross_sell_ids'        => 'Cross-sells', // List of cross-sell products IDs.
	'parent_id'             => 'Parent', // Product parent ID.
	'purchase_note'         => 'Purchase note', // Optional note to send the customer after purchase.
	'categories'            => 'Categories', // List of categories.
	'tags'                  => 'Tags', // List of tags.
	'images'                => 'Images', // List of images.
	// todo later: process columns: Attribute 1 name    Attribute 1 value(s)    Attribute 1 visible Attribute 1 global  Attribute 2 name    Attribute 2 value(s)    Attribute 2 visible Attribute 2 global
	'attributes'            => '', // List of attributes.
	'default_attributes'    => '', // Defaults variation attributes.
	'menu_order'            => '', // Menu order, used to custom sort products.
	'meta_data'             => '', // Meta data.
	'porcelain'             => '', // Output just the id when the operation is successful.
];

function packeteryExtractProductCreateCommand( array $mapping, array $categoriesMapping, string $wpUser, array $productData ): void {
	$parameters = [];

	foreach ( $mapping as $parameterName => $csvHeading ) {
		// some more complicated values
		if ( $parameterName === 'dimensions' ) {
			$rawValue = [
				'length' => $productData['Length (cm)'],
				'width'  => $productData['Width (cm)'],
				'height' => $productData['Height (cm)'],
			];
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$parameters[] = '--' . $parameterName . '=' . escapeshellarg( json_encode( $rawValue ) );
		}

		// skip missing mappings and empty cells
		if ( $csvHeading === '' || $productData[ $csvHeading ] === '' ) {
			continue;
		}

		$parameterValue = $productData[ $csvHeading ];

		if ( $parameterName === 'categories' ) {
			$endCategory = packeteryGetEndCategory( $parameterValue );
			if ( $categoriesMapping[ $endCategory ] ) {
				$parameterValue = '[{"id":' . $categoriesMapping[ $endCategory ] . '}]';
			}
		}
		if ( $parameterName === 'backorders' ) {
			$parameterValue = ( (int) $parameterValue === 1 ? 'yes' : 'no' );
		}
		if ( $parameterName === 'parent_id' ) {
			// todo, maybe later: parent_id is not of type integer
			continue;
		}
		if ( $parameterName === 'images' ) {
			// todo, maybe later: images[0] is not of type object
			continue;
		}

		$parameters[] = '--' . $parameterName . '=' . escapeshellarg( $parameterValue );
	}

	$metaData = [];
	if ( $productData['Meta: packetery_age_verification_18_plus'] === '1' ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$metaData[] = json_encode(
			[
				'key'   => 'packetery_age_verification_18_plus',
				'value' => '1',
			]
		);
	}
	if ( $productData['Meta: packetery_disallowed_shipping_rates'] !== '' ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$metaData[] = json_encode(
			[
				'key'   => 'packetery_disallowed_shipping_rates',
				'value' => json_decode( $productData['Meta: packetery_disallowed_shipping_rates'], true ),
			]
		);
	}
	if ( count( $metaData ) > 0 ) {
		$parameters[] = '--meta_data=' . escapeshellarg( '[' . implode( ',', $metaData ) . ']' );
	}

	$downloads = [];
	foreach ( [ 1, 2 ] as $downloadColumn ) {
		$nameKey = 'Download ' . $downloadColumn . ' name';
		$urlKey  = 'Download ' . $downloadColumn . ' URL';

		if ( $productData[ $nameKey ] !== '' && $productData[ $urlKey ] !== '' ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$downloads[] = json_encode(
				[
					'name' => $productData[ $nameKey ],
					'file' => $productData[ $urlKey ],
				]
			);
		}
	}
	if ( count( $downloads ) > 0 ) {
		$parameters[] = '--downloadable=1';
		$parameters[] = '--downloads=[' . implode( ',', $downloads ) . ']';
	}

	$cliCommand  = 'wp wc product create --user="' . $wpUser . '"';
	$cliCommand .= ' ' . implode( ' ', $parameters );
	$cliCommand .= ' --allow-root=1';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $cliCommand . "\n";
}

function packeteryGetEndCategory( string $parameterValue ) {
	$categoryTree = explode( ' > ', $parameterValue );

	return $categoryTree[ count( $categoryTree ) - 1 ];
}

function packeteryGetCategoryList( array $categories, array $productData ): array {
	$endCategory = packeteryGetEndCategory( $productData['Categories'] );
	if ( (string) $endCategory !== '' && ! in_array( $endCategory, $categories, true ) ) {
		$categories[] = $endCategory;
	}

	return $categories;
}

function packeteryExtractCategoryCreateCommand( string $wpUser, string $category ): void {
	$cliCommand  = 'wp wc product_cat create --user="' . $wpUser . '" --name="' . $category . '"';
	$cliCommand .= ' --allow-root=1';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $cliCommand . "\n";
}

function parseCategoriesTsv( $filePath ): array {
	$categoriesMapping = [];

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	$handle = fopen( $filePath, 'rb' );
	if ( $handle !== false ) {
		$headers = fgetcsv( $handle, 0, "\t" );

		$idIndex   = array_search( 'id', $headers, true );
		$nameIndex = array_search( 'name', $headers, true );

		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( $row = fgetcsv( $handle, 0, "\t" ) ) {
			if ( isset( $row[ $idIndex ], $row[ $nameIndex ] ) ) {
				$categoriesMapping[ $row[ $nameIndex ] ] = (int) $row[ $idIndex ];
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );
	} else {
		throw new RuntimeException( "Unable to open the file: $filePath" );
	}

	return $categoriesMapping;
}

$categoriesMapping = [];
if ( $parserType === 'product' ) {
	if ( ! file_exists( $categoriesFile ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( STDERR, "🛑 CSV with categories not found.\n" );
		exit( 1 );
	}
	$categoriesMapping = parseCategoriesTsv( $categoriesFile );
}

if ( ! file_exists( $csvFile ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	fwrite( STDERR, "🛑 CSV with products not found.\n" );
	exit( 1 );
}
$data     = array_map( 'str_getcsv', file( $csvFile ) );
$headers  = array_map( 'trim', $data[0] );
$products = array_slice( $data, 1 );

$categories = [];
foreach ( $products as $product ) {
	$productData = array_combine( $headers, $product );

	if ( $parserType === 'product' ) {
		packeteryExtractProductCreateCommand( $mapping, $categoriesMapping, $wpUser, $productData );

		continue;
	}

	if ( $parserType === 'category' ) {
		$categories = packeteryGetCategoryList( $categories, $productData );
	}
}

if ( $parserType === 'category' ) {
	foreach ( $categories as $category ) {
		packeteryExtractCategoryCreateCommand( $wpUser, $category );
	}
}
