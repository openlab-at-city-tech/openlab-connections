<?php
/**
 * Header template for Connections.
 *
 * @package openlab-connections
 */

?>

<p>
	<?php
	echo wp_kses_post(
		sprintf(
			// @todo This will need to be generalized, and this template overloaded for City Tech.
			// translators: OpenLab Help link.
			__( 'This feature connects related spaces on the OpenLab. It is useful for sharing site activity across connected Courses, Projects, or Clubs. Visit OpenLab Help to <a class="external-link" href="%s">learn more</a>.', 'openlab-connections' ),
			'https://openlab.citytech.cuny.edu/blog/help/openlab-connections'
		)
	);
	?>
</p>
